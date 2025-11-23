<?php

namespace App\Livewire;

use App\Events\GameCountdownStarted;
use App\Events\GameSessionStateChanged;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Livewire\Attributes\On;
use Livewire\Component;

class RunSession extends Component
{
    public GameSession $gameSession;
    public ?SessionQuestion $current = null;
    public bool $countdownActive = false;

    // Inicializar el componente
    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession->fresh();
        $this->loadCurrent();
    }

    public function extendTime(int $seconds = 15): void
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        if (!$this->gameSession->is_running || $this->gameSession->is_paused || !$this->current) {
            $this->dispatch('toast', body: 'No hay una pregunta corriendo para ajustar tiempo.');
            return;
        }

        $started = $this->gameSession->current_q_started_at;
        if (!$started) {
            $this->dispatch('toast', body: 'El cronómetro aún no ha iniciado.');
            return;
        }

        $duration = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
        $elapsed  = $started->diffInSeconds(now());
        // Permitir incluso si ya estaba por expirar: simplemente ampliamos la duración total
        $newDuration = max(5, $duration + max(1, $seconds));

        $this->current->update(['timer_override' => $newDuration]);

        $this->loadCurrent();   // para que la vista actualice data-duration
        $this->broadcastState(); // notifica a pantallas/clients
        $left = max(0, $newDuration - $elapsed);
        $this->dispatch('toast', body: "Tiempo extendido. Restan ~{$left}s");
    }

    public function reduceTime(int $seconds = 5): void
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        if (!$this->gameSession->is_running || $this->gameSession->is_paused || !$this->current) {
            $this->dispatch('toast', body: 'No hay una pregunta corriendo para ajustar tiempo.');
            return;
        }

        $started = $this->gameSession->current_q_started_at;
        if (!$started) {
            $this->dispatch('toast', body: 'El cronómetro aún no ha iniciado.');
            return;
        }

        $duration = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
        $elapsed  = $started->diffInSeconds(now());
        $left     = $duration - $elapsed;

        // Regla: si quedan ≤ 5s ya no se puede reducir
        if ($left <= 5) {
            $this->dispatch('toast', body: 'Quedan 5 segundos o menos — ya no se puede reducir el tiempo.');
            return;
        }

        // Reducimos pero garantizando al menos 5s restantes
        $reduceBy    = max(1, $seconds);
        $newDuration = max($elapsed + 5, $duration - $reduceBy);

        $this->current->update(['timer_override' => $newDuration]);

        $this->loadCurrent();
        $this->broadcastState();
        $newLeft = max(0, $newDuration - $elapsed);
        $this->dispatch('toast', body: "Tiempo reducido. Restan ~{$newLeft}s");
    }


    #[On('checkTimeout')]
    public function checkTimeout(): void
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        // Debe haber una pregunta corriendo y no estar en pausa
        if (!$this->gameSession->is_running || $this->gameSession->is_paused || !$this->current) {
            return;
        }

        $started  = $this->gameSession->current_q_started_at;
        if (!$started) return;

        // Duración (override o default)
        $duration = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
        if ($duration <= 0) return;

        // ¿Se agotó el tiempo?
        $elapsed = $started->diffInSeconds(now());
        if ($elapsed < $duration) return;

        // ¿NADIE respondió?
        $answers = Answer::where('session_question_id', $this->current->id)->count();

        // Revela (pausa) automáticamente
        $this->gameSession->update(['is_paused' => true]);
        $this->broadcastState();
        $this->dispatch(
            'toast',
            body: $answers > 0
                ? 'Tiempo agotado — se revela la respuesta.'
                : 'Tiempo agotado — se revela la respuesta (sin respuestas registradas).'
        );
    }
    // Cargar/refrescar la pregunta actual y métricas asociadas
    public function loadCurrent(): void
    {
        $index = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $index)->first();
    }

    public function start(): void
    {
        // ⚠️ No iniciar si no hay participantes
        if ($this->activeParticipantsCount() < 1) {
            $this->dispatch('toast', body: 'Necesitas al menos 1 participante para iniciar.');
            return;
        }

        // Activa la partida pero aún NO arranca el cronómetro real
        $this->gameSession->update([
            'is_active'            => true,
            'is_running'           => false,
            'is_paused'            => false,
            'current_q_started_at' => null,
        ]);

        // (Opcional) Si usas el flag de UI para ocultar "Iniciar" durante el conteo:
        if (property_exists($this, 'countdownActive')) {
            $this->countdownActive = true;
        }

        GameCountdownStarted::dispatch(
            $this->gameSession->id,
            3,
            'start',
            $this->gameSession->current_q_index
        );

        // RUN hace el 3-2-1 y luego llama a $wire.startNow()
        $this->dispatch('countdown', action: 'start');
        $this->dispatch('toast', body: 'Inicio en 3…');
    }

    public function startNow(): void
    {
        // Defensa extra por si alguien dispara startNow() sin participantes
        if ($this->activeParticipantsCount() < 1) {
            $this->dispatch('toast', body: 'No hay participantes activos. No se puede iniciar.');
            if (property_exists($this, 'countdownActive')) $this->countdownActive = false;
            return;
        }

        $this->gameSession->refresh();

        $this->gameSession->update([
            'is_active'            => true,
            'is_running'           => true,
            'is_paused'            => false,
            'current_q_started_at' => now(),
        ]);

        if (property_exists($this, 'countdownActive')) {
            $this->countdownActive = false;
        }

        $this->loadCurrent();
        $this->broadcastState();
        $this->dispatch('toast', body: '¡Arrancamos!');
    }
    // Pausar/Reanudar
    public function togglePause(): void
    {
        $this->gameSession->update(['is_paused' => !$this->gameSession->is_paused]);
        $this->gameSession->refresh();
        $this->broadcastState();
    }

    /** Revela la respuesta correcta (pausa para explicación) */
    #[On('revealAndPause')]
    public function revealAndPause(): void
    {
        $this->gameSession->refresh();
        if (! $this->gameSession->is_paused) {
            $this->gameSession->update(['is_paused' => true]);
            // No guardamos un flag aparte: los alumnos determinan correcta leyendo la opción correcta
            $this->dispatch('toast', body: 'Respuesta revelada y pausa activa');
            $this->broadcastState(); // hará que el RUN/SCRREEN se refresquen
        }
    }

    # Ajusta nextQuestion para usar el mismo patrón de conteo
    public function nextQuestion(): void
    {
        $next = $this->gameSession->current_q_index + 1;

        if ($next >= $this->gameSession->questions_total) {
            $this->gameSession->update([
                'is_active'            => false,
                'is_running'           => false,
                'is_paused'            => false,
                'current_q_index'      => $this->gameSession->questions_total,
                'current_q_started_at' => null,
            ]);
            $this->countdownActive = false;
            $this->broadcastState();
            $this->redirectRoute('winners', ['gameSession' => $this->gameSession->id], navigate: true);
            return;
        }

        $this->countdownActive = true; // <- ocultar "Iniciar" / mostrar resto durante el conteo

        GameCountdownStarted::dispatch(
            $this->gameSession->id,
            3,
            'advance',
            $next
        );

        $this->dispatch('countdown', action: 'advance');
    }

    /** Listener desde JS tras el conteo */
    #[On('advanceNow')]
    public function advanceNow(): void
    {
        $this->gameSession->refresh();

        if (! $this->gameSession->is_paused) {
            $this->revealAndPause();
            return;
        }

        $this->gameSession->increment('current_q_index');
        $this->gameSession->update([
            'is_paused'            => false,
            'is_running'           => true,
            'current_q_started_at' => now(),
        ]);

        $this->countdownActive = false; // <- terminó el conteo
        $this->loadCurrent();
        $this->broadcastState();
    }

    // Contar cuántos participantes deben responder.
    private function participantsCount(): int
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)->count();
    }

    /** Resumen por opción: [ ['label'=>'A','option_id'=>1,'count'=>5,'is_correct'=>true], ... ] */
    private function optionDistribution(): array
    {
        if (!$this->current) return [];

        $opts = $this->current->question->options()->orderBy('opt_order')->get(['id', 'label', 'is_correct']);
        $counts = Answer::selectRaw('question_option_id, COUNT(*) as c')
            ->where('session_question_id', $this->current->id)
            ->whereNotNull('question_option_id')
            ->groupBy('question_option_id')
            ->pluck('c', 'question_option_id');

        return $opts->map(function ($o) use ($counts) {
            return [
                'label'      => $o->label,
                'option_id'  => $o->id,
                'count'      => (int)($counts[$o->id] ?? 0),
                'is_correct' => (bool)$o->is_correct,
            ];
        })->toArray();
    }

    /** Total que ya respondió (incluye los que no marcaron opción y quedaron en null) */
    private function answeredCount(): int
    {
        if (!$this->current) return 0;
        return Answer::where('session_question_id', $this->current->id)->count();
    }

    /** Correctas registradas */
    private function correctCount(): int
    {
        if (!$this->current) return 0;
        return Answer::where('session_question_id', $this->current->id)->where('is_correct', true)->count();
    }

    /** Top 5 en vivo (score desc, tiempo asc) */
    private function liveTop(): \Illuminate\Support\Collection
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user:id,name')
            ->orderByDesc('score')->orderBy('time_total_ms')->take(5)->get();
    }

    // Auto-revelar cuando todos ya contestaron.
    private function revealIfAllAnswered(): void
    {
        if (!$this->gameSession->is_running || $this->gameSession->is_paused || !$this->current) return;

        $pActivos = $this->activeParticipantsCount();
        if ($pActivos === 0) return;

        $aActivos = $this->answeredActiveCount();

        if ($aActivos >= $pActivos) {
            $this->gameSession->update(['is_paused' => true]);
            $this->broadcastState();
            $this->dispatch('toast', body: 'Respondieron los participantes activos — respuesta revelada');
        }
    }

    // Enviar un snapshot del estado a los clientes.
    private function broadcastState(): void
    {
        $s = $this->gameSession->fresh();
        $duration = (int) ($this->current?->timer_override ?? $s->timer_default);

        GameSessionStateChanged::dispatch($s->id, [
            'is_active'             => $s->is_active,
            'is_running'            => $s->is_running,
            'is_paused'             => $s->is_paused,
            'current_q_index'       => $s->current_q_index,
            'current_q_started_at'  => optional($s->current_q_started_at)?->toIso8601String(),
            'questions_total'       => $s->questions_total,
            'duration'              => $duration, // <- extra; tu cliente puede ignorarlo si no lo usa
        ]);
    }

    #[On('syncState')]
    public function syncState(): void
    {
        $this->gameSession = $this->gameSession->fresh();
        $this->loadCurrent();
    }

    private function correctCountActive(): int
    {
        if (!$this->current) return 0;

        $activeIds = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->pluck('id');

        return Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $activeIds)
            ->where('is_correct', true)
            ->count();
    }

    private function optionDistributionActive(): array
    {
        if (!$this->current) return [];

        // 👉 Si es pregunta corta, no hay distribución de alternativas
        if ($this->current->question->qtype === 'short') {
            return [];
        }

        $activeIds = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->pluck('id');

        $opts = $this->current->question->options()->orderBy('opt_order')->get(['id', 'label', 'is_correct']);

        $counts = Answer::selectRaw('question_option_id, COUNT(*) as c')
            ->where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $activeIds)
            ->whereNotNull('question_option_id')
            ->groupBy('question_option_id')
            ->pluck('c', 'question_option_id');

        return $opts->map(fn($o) => [
            'label'      => $o->label,
            'option_id'  => $o->id,
            'count'      => (int)($counts[$o->id] ?? 0),
            'is_correct' => (bool)$o->is_correct,
        ])->toArray();
    }

    // Solo participantes activos (no ignorados) en esta sesión
    private function activeParticipantsCount(): int
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->count();
    }

    // Respondidos entre los activos (para la métrica “Respondidos / Activos”)
    private function answeredActiveCount(): int
    {
        if (! $this->current) return 0;

        $activeIds = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->pluck('id');

        return Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $activeIds)
            ->count();
    }

    // Alternar ignorado desde la pantalla RUN
    public function toggleIgnore(int $participantId): void
    {
        $p = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('id', $participantId)
            ->firstOrFail();

        $p->update(['is_ignored' => ! $p->is_ignored]);

        $this->dispatch('toast', body: $p->is_ignored
            ? 'Participante marcado como inactivo (ignorado).'
            : 'Participante reactivado.');

        $this->broadcastState();
        $this->dispatch('render');
    }

    public function markShortCorrect(int $answerId, bool $correct = true): void
    {
        // Asegurar que haya pregunta actual
        if (! $this->current) {
            return;
        }

        // Buscar la respuesta SOLO de la pregunta actual (por seguridad)
        $answer = Answer::where('id', $answerId)
            ->where('session_question_id', $this->current->id)
            ->first();

        if (! $answer) {
            return;
        }

        // Marcar como correcta/incorrecta
        $answer->is_correct = $correct;
        $answer->score      = $correct ? 1 : 0; // si luego quieres ponderar, aquí lo cambias
        $answer->save();

        // Recalcular totales del participante
        $sum = Answer::where('session_participant_id', $answer->session_participant_id);

        SessionParticipant::where('id', $answer->session_participant_id)->update([
            'score'         => (clone $sum)->where('is_correct', true)->count(),
            'time_total_ms' => (clone $sum)->sum('time_ms'),
        ]);

        $this->dispatch(
            'toast',
            body: $correct ? 'Respuesta marcada como CORRECTA.' : 'Respuesta marcada como INCORRECTA.'
        );
    }

    /** Lista completa de participantes ordenados por score DESC y tiempo ASC */
    private function participantsList(): \Illuminate\Support\Collection
    {
        return SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->get();
    }

    // Renderizar la vista Livewire.
    #[On('render')]
    public function render()
    {
        $this->gameSession->refresh();
        $this->loadCurrent();
        $this->revealIfAllAnswered();

        $pCount   = $this->activeParticipantsCount();
        $answered = $this->answeredActiveCount();
        $corrects = $this->correctCountActive();
        $dist     = $this->optionDistributionActive();
        $top      = $this->liveTop();
        $participants = $this->participantsList();

        // ▼ NUEVO: respuestas cortas de la pregunta actual
        $shortAnswers = collect();
        $shortParticipants = collect();

        if ($this->current && $this->current->question && $this->current->question->qtype === 'short') {
            $shortAnswers = Answer::where('session_question_id', $this->current->id)
                ->orderBy('created_at')
                ->get();

            $shortParticipants = SessionParticipant::with('user:id,name')
                ->whereIn('id', $shortAnswers->pluck('session_participant_id')->unique())
                ->get()
                ->keyBy('id');
        }

        return view('livewire.run-session', compact(
            'pCount',
            'answered',
            'corrects',
            'dist',
            'top',
            'participants',
            'shortAnswers',       // ▼ NUEVO
            'shortParticipants'   // ▼ NUEVO
        ))
            ->layout('layouts.adminlte', [
                'title'  => 'Ejecutar Partida',
                'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
            ]);
    }
}
