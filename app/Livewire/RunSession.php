<?php

namespace App\Livewire;

use App\Events\GameCountdownStarted;
use App\Models\Answer;
use App\Models\DeviceLock;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class RunSession extends Component
{
    public GameSession $gameSession;

    public ?SessionQuestion $current = null;

    /** true mientras corre el conteo 3-2-1 (oculta "Iniciar" y evita dobles avances) */
    public bool $countdownActive = false;

    public function mount(GameSession $gameSession)
    {
        abort_unless($gameSession->isManageableBy(auth()->user()), 403, 'No tienes acceso a esta partida.');

        $this->gameSession = $gameSession->fresh();

        if ($this->gameSession->sessionQuestions()->count() === 0) {
            session()->flash('error', 'Esta partida no tiene preguntas configuradas. Complétalas desde Partidas.');
            $this->redirect(route('sessions.index'));
        } else {
            $this->loadCurrent();
        }
    }

    /* ======================== Cronómetro ======================== */

    public function extendTime(int $seconds = 15): void
    {
        if (! $started = $this->runningQuestionStart()) {
            return;
        }

        $duration = $this->currentDuration();
        $elapsed = $started->diffInSeconds(now());
        $newDuration = max(5, $duration + max(1, $seconds));

        $this->current->update(['timer_override' => $newDuration]);
        $this->broadcastState();

        $left = (int) max(0, $newDuration - $elapsed);
        $this->dispatch('toast', body: "Tiempo extendido. Restan ~{$left}s");
    }

    public function reduceTime(int $seconds = 5): void
    {
        if (! $started = $this->runningQuestionStart()) {
            return;
        }

        $duration = $this->currentDuration();
        $elapsed = $started->diffInSeconds(now());

        // Regla: si quedan ≤ 5s ya no se puede reducir
        if ($duration - $elapsed <= 5) {
            $this->dispatch('toast', body: 'Quedan 5 segundos o menos — ya no se puede reducir el tiempo.');

            return;
        }

        // Reducimos garantizando al menos 5s restantes
        $newDuration = (int) ceil(max($elapsed + 5, $duration - max(1, $seconds)));

        $this->current->update(['timer_override' => $newDuration]);
        $this->broadcastState();

        $left = (int) max(0, $newDuration - $elapsed);
        $this->dispatch('toast', body: "Tiempo reducido. Restan ~{$left}s");
    }

    /** Devuelve el inicio de la pregunta en curso o null (avisando al docente). */
    private function runningQuestionStart(): ?\Carbon\CarbonInterface
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        if (! $this->gameSession->is_running || $this->gameSession->is_paused || ! $this->current) {
            $this->dispatch('toast', body: 'No hay una pregunta corriendo para ajustar tiempo.');

            return null;
        }

        if (! $this->gameSession->current_q_started_at) {
            $this->dispatch('toast', body: 'El cronómetro aún no ha iniciado.');

            return null;
        }

        return $this->gameSession->current_q_started_at;
    }

    private function currentDuration(): int
    {
        return (int) ($this->current?->timer_override ?? $this->gameSession->timer_default);
    }

    #[On('checkTimeout')]
    public function checkTimeout(): void
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        if (! $this->gameSession->is_running || $this->gameSession->is_paused || ! $this->current) {
            return;
        }

        $started = $this->gameSession->current_q_started_at;
        $duration = $this->currentDuration();
        if (! $started || $duration <= 0 || $started->diffInSeconds(now()) < $duration) {
            return;
        }

        $hasAnswers = Answer::where('session_question_id', $this->current->id)->exists();

        // Revela (pausa) automáticamente
        $this->gameSession->update(['is_paused' => true]);
        $this->broadcastState();
        $this->dispatch(
            'toast',
            body: $hasAnswers
                ? 'Tiempo agotado — se revela la respuesta.'
                : 'Tiempo agotado — se revela la respuesta (sin respuestas registradas).'
        );
    }

    public function loadCurrent(): void
    {
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $this->gameSession->current_q_index)
            ->first();
    }

    /* ======================== Flujo de la partida ======================== */

    public function start(): void
    {
        if ($this->activeParticipantsCount() < 1) {
            $this->dispatch('toast', body: 'Necesitas al menos 1 participante para iniciar.');

            return;
        }

        // Confirmación en frontend (SweetAlert).
        $this->dispatch('run-confirm-start');
    }

    #[On('run-start-confirmed')]
    public function startConfirmed(): void
    {
        if ($this->countdownActive) {
            return; // ya hay un conteo en curso
        }

        if ($this->activeParticipantsCount() < 1) {
            $this->dispatch('toast', body: 'Necesitas al menos 1 participante para iniciar.');

            return;
        }

        // Activa la partida pero aún NO arranca el cronómetro real.
        $this->gameSession->update([
            'is_active' => true,
            'is_running' => false,
            'is_paused' => false,
            'current_q_started_at' => null,
        ]);

        $this->countdownActive = true;

        GameCountdownStarted::dispatch($this->gameSession->id, 3, 'start', $this->gameSession->current_q_index);

        // RUN hace el 3-2-1 y luego llama a $wire.startNow()
        $this->dispatch('countdown', action: 'start');
        $this->dispatch('toast', body: 'Inicio en 3…');
    }

    public function startNow(): void
    {
        $this->countdownActive = false;

        if ($this->activeParticipantsCount() < 1) {
            $this->dispatch('toast', body: 'No hay participantes activos. No se puede iniciar.');

            return;
        }

        $this->gameSession->refresh();
        if ($this->gameSession->is_running) {
            return; // ya iniciada (doble llamada)
        }

        $this->gameSession->update([
            'is_active' => true,
            'is_running' => true,
            'is_paused' => false,
            'current_q_started_at' => now(),
        ]);

        $this->loadCurrent();
        $this->broadcastState();
        $this->dispatch('toast', body: '¡Arrancamos!');
    }

    /** Pausar / Reanudar sin "consumir" el tiempo que dura la pausa. */
    public function togglePause(): void
    {
        $this->gameSession->refresh();
        $key = $this->pausedAtKey();

        if (! $this->gameSession->is_paused) {
            Cache::put($key, now()->getTimestampMs(), now()->addHours(6));
            $this->gameSession->update(['is_paused' => true]);
        } else {
            $changes = ['is_paused' => false];
            $pausedAt = Cache::pull($key);
            $started = $this->gameSession->current_q_started_at;

            // Desplazamos el inicio lo que duró la pausa: el tiempo pausado no cuenta
            if ($pausedAt && $started) {
                $pausedMs = max(0, now()->getTimestampMs() - (int) $pausedAt);
                $changes['current_q_started_at'] = $started->copy()->addMilliseconds($pausedMs);
            }

            $this->gameSession->update($changes);
        }

        $this->broadcastState();
    }

    private function pausedAtKey(): string
    {
        return "game_session:{$this->gameSession->id}:paused_at";
    }

    /** Revela la respuesta correcta (pausa para explicación) */
    #[On('revealAndPause')]
    public function revealAndPause(): void
    {
        $this->gameSession->refresh();
        if (! $this->gameSession->is_paused) {
            Cache::forget($this->pausedAtKey());
            $this->gameSession->update(['is_paused' => true]);
            $this->dispatch('toast', body: 'Respuesta revelada y pausa activa');
            $this->broadcastState();
        }
    }

    public function nextQuestion(): void
    {
        if ($this->countdownActive) {
            return; // evita doble avance por doble clic
        }

        $this->gameSession->refresh();
        $this->loadCurrent();

        if ($this->hasPendingShortAnswersToGrade()) {
            $this->dispatch('run-short-needs-grading');

            return;
        }

        $next = $this->gameSession->current_q_index + 1;

        if ($next >= $this->gameSession->questions_total) {
            Cache::forget($this->pausedAtKey());
            $this->gameSession->update([
                'is_active' => false,
                'is_running' => false,
                'is_paused' => false,
                'current_q_index' => $this->gameSession->questions_total,
                'current_q_started_at' => null,
            ]);
            $this->broadcastState();
            $this->redirectRoute('winners', ['gameSession' => $this->gameSession->id], navigate: true);

            return;
        }

        $this->countdownActive = true;

        GameCountdownStarted::dispatch($this->gameSession->id, 3, 'advance', $next);

        $this->dispatch('countdown', action: 'advance', target: $next);
    }

    /** Llamado desde JS tras el conteo 3-2-1 */
    #[On('advanceNow')]
    public function advanceNow(?int $target = null): void
    {
        $this->countdownActive = false;
        $this->gameSession->refresh();

        // Conteo obsoleto: ya se avanzó (p. ej. otra pestaña del docente)
        if ($target !== null && $this->gameSession->current_q_index + 1 !== $target) {
            return;
        }

        if (! $this->gameSession->is_paused) {
            $this->revealAndPause();

            return;
        }

        Cache::forget($this->pausedAtKey());
        $this->gameSession->update([
            'current_q_index' => $this->gameSession->current_q_index + 1,
            'is_paused' => false,
            'is_running' => true,
            'current_q_started_at' => now(),
        ]);

        $this->loadCurrent();
        $this->broadcastState();
    }

    #[On('syncState')]
    public function syncState(): void
    {
        $this->gameSession->refresh();
        $this->loadCurrent();
    }

    /** Llega por broadcast cuando un alumno responde: basta con re-renderizar. */
    #[On('refreshStats')]
    public function refreshStats(): void {}

    /* ======================== Participantes y calificación ======================== */

    public function toggleIgnore(int $participantId): void
    {
        $p = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->whereKey($participantId)
            ->firstOrFail();

        $p->update(['is_ignored' => ! $p->is_ignored]);

        $this->dispatch('toast', body: $p->is_ignored
            ? 'Participante marcado como inactivo (ignorado).'
            : 'Participante reactivado.');

        $this->broadcastState();
    }

    /** Borra el bloqueo de dispositivo para que el alumno reingrese desde otro equipo. */
    public function releaseDevice(int $participantId): void
    {
        $p = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->whereKey($participantId)
            ->firstOrFail();

        DeviceLock::where('game_session_id', $this->gameSession->id)
            ->where('user_id', $p->user_id)
            ->delete();

        $this->dispatch('toast', body: 'Dispositivo liberado: el alumno puede reingresar con el código.');
    }

    public function markShortCorrect(int $answerId, bool $correct = true): void
    {
        if (! $this->current) {
            return;
        }

        // Solo respuestas de la pregunta actual (por seguridad)
        $answer = Answer::whereKey($answerId)
            ->where('session_question_id', $this->current->id)
            ->first();

        if (! $answer) {
            return;
        }

        $answer->is_correct = $correct;
        $answer->score = $correct ? 1 : 0;
        $answer->graded_at = now();
        $answer->save();

        // Recalcular totales del participante en una sola consulta
        $totals = Answer::where('session_participant_id', $answer->session_participant_id)
            ->selectRaw('COALESCE(SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END),0) AS score, COALESCE(SUM(time_ms),0) AS time_total_ms')
            ->first();

        SessionParticipant::whereKey($answer->session_participant_id)
            ->update($totals->only(['score', 'time_total_ms']));

        $this->dispatch(
            'toast',
            body: $correct ? 'Respuesta marcada como CORRECTA.' : 'Respuesta marcada como INCORRECTA.'
        );
    }

    /* ======================== Helpers ======================== */

    private function activeParticipantIds(): Collection
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->pluck('id');
    }

    private function activeParticipantsCount(): int
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->count();
    }

    /** Si la pregunta actual es abierta, exige calificar todas las respuestas activas. */
    private function hasPendingShortAnswersToGrade(): bool
    {
        if ($this->current?->question?->qtype !== 'short') {
            return false;
        }

        return Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $this->activeParticipantIds())
            ->whereNotNull('answered_at')
            ->where('is_correct', false)
            ->whereNull('graded_at')
            ->exists();
    }

    /**
     * Métricas de la pregunta actual en UNA consulta agregada (solo participantes activos):
     * respondidos, correctas y conteo por alternativa.
     */
    private function currentStats(Collection $activeIds): array
    {
        if (! $this->current || $activeIds->isEmpty()) {
            return ['answered' => 0, 'corrects' => 0, 'byOption' => collect()];
        }

        $rows = Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $activeIds)
            ->selectRaw('question_option_id, COUNT(*) AS c, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS ok')
            ->groupBy('question_option_id')
            ->get();

        return [
            'answered' => (int) $rows->sum('c'),
            'corrects' => (int) $rows->sum('ok'),
            'byOption' => $rows->whereNotNull('question_option_id')->pluck('c', 'question_option_id'),
        ];
    }

    /** Auto-revelar cuando todos los activos ya contestaron. */
    private function revealIfAllAnswered(int $pActivos, int $aActivos): void
    {
        if (! $this->gameSession->is_running || $this->gameSession->is_paused || ! $this->current) {
            return;
        }

        if ($pActivos > 0 && $aActivos >= $pActivos) {
            $this->gameSession->update(['is_paused' => true]);
            $this->broadcastState();
            $this->dispatch('toast', body: 'Respondieron los participantes activos — respuesta revelada');
        }
    }

    /** Envía un snapshot del estado a todos los clientes. */
    private function broadcastState(): void
    {
        $this->gameSession->fresh()->broadcastState($this->currentDuration());
    }

    #[On('render')]
    public function render()
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        // Participantes: UNA consulta (ordenada por ranking) reutilizada para conteo, top y lista
        $participants = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->get();

        $activeIds = $participants->where('is_ignored', false)->pluck('id');
        $pCount = $activeIds->count();
        $top = $participants->take(5);

        $stats = $this->currentStats($activeIds);
        $answered = $stats['answered'];
        $corrects = $stats['corrects'];

        $this->revealIfAllAnswered($pCount, $answered);

        $dist = [];
        $shortAnswers = collect();
        $shortParticipants = collect();

        if ($this->current?->question) {
            if ($this->current->question->qtype === 'short') {
                $shortAnswers = Answer::where('session_question_id', $this->current->id)
                    ->orderBy('created_at')
                    ->get();
                $shortParticipants = $participants->keyBy('id')
                    ->only($shortAnswers->pluck('session_participant_id')->unique()->all());
            } else {
                $dist = $this->current->question->options
                    ->sortBy('opt_order')
                    ->map(fn ($o) => [
                        'label' => $o->label,
                        'option_id' => $o->id,
                        'count' => (int) ($stats['byOption'][$o->id] ?? 0),
                        'is_correct' => (bool) $o->is_correct,
                    ])->values()->toArray();
            }
        }

        return view('livewire.run-session', compact(
            'pCount',
            'answered',
            'corrects',
            'dist',
            'top',
            'participants',
            'shortAnswers',
            'shortParticipants'
        ))->layout('layouts.adminlte', [
            'title' => 'Ejecutar Partida',
            'header' => ($this->gameSession->title ?? 'Partida').' ['.$this->gameSession->code.']',
        ]);
    }
}
