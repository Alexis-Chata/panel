<?php

namespace App\Livewire;

use App\Events\AnswerSubmitted;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PlayBasic extends Component
{
    /** Segundos de tolerancia (latencia de red) para aceptar respuestas tras vencer el tiempo */
    private const GRACE_SECONDS = 2;

    public GameSession $gameSession;

    public ?SessionParticipant $me = null;

    public ?SessionQuestion $current = null;

    public ?int $answered_option_id = null;

    public ?int $last_seen_index = null;

    public string $respuesta = '';

    public bool $answered_any = false;

    /** @var array<int,array{id:int,name:string,first_name:string,photo_url:string}> */
    public array $roster = [];

    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession->fresh();
        $this->me = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('user_id', Auth::id())->first();

        abort_unless($this->me, 403, 'No estás unido a esta partida.');

        $this->syncCurrent();
        $this->refreshRoster();
    }

    public function syncCurrent(): void
    {
        $idx = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $idx)->first();

        $this->last_seen_index = $idx;

        $ans = $this->current
            ? Answer::where('session_participant_id', $this->me->id)
                ->where('session_question_id', $this->current->id)->first()
            : null;

        $this->answered_option_id = $ans?->question_option_id;
        $this->answered_any = (bool) $ans;
        $this->respuesta = ($this->current?->question->qtype === 'short') ? ($ans?->text ?? '') : '';
    }

    /** Lista de avatares del lobby (Jetstream ya resuelve foto o avatar por defecto). */
    private function buildRoster(): array
    {
        return SessionParticipant::with(['user:id,name,email,profile_photo_path'])
            ->where('game_session_id', $this->gameSession->id)
            ->orderBy('id')
            ->get()
            ->map(function ($p) {
                $u = $p->user;
                $name = (string) ($u->name ?? '');

                return [
                    'id' => (int) $p->id,
                    'name' => $name,
                    'first_name' => explode(' ', trim($name))[0] ?? '',
                    'photo_url' => (string) ($u->profile_photo_url
                        ?? 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=0D8ABC&color=fff&size=128'),
                ];
            })
            ->values()
            ->toArray();
    }

    #[On('refreshRoster')]
    public function refreshRoster(): void
    {
        // El roster solo se muestra en la sala de espera
        if ($this->gameSession->is_running) {
            return;
        }
        $this->roster = $this->buildRoster();
    }

    /** Respuesta de alternativa (o null = sin respuesta al vencer el tiempo). */
    public function answer(?int $optionId, $ignoreElapsedFromClient = null)
    {
        if (! $this->canAnswer()) {
            return;
        }

        $question = $this->current->question;
        $isShort = $question->qtype === 'short';

        // En preguntas cortas solo se acepta el "sin respuesta" del temporizador
        if ($isShort && $optionId !== null) {
            return;
        }

        $option = null;
        if ($optionId !== null) {
            $option = $question->options->firstWhere('id', $optionId);
            if (! $option) {
                return; // opción que no pertenece a la pregunta
            }
        }

        $saved = $this->storeAnswer([
            'question_option_id' => $option?->id,
            'is_correct' => (bool) $option?->is_correct,
            // Una pregunta corta sin respuesta queda calificada (incorrecta) automáticamente
            'graded_at' => $isShort ? now() : null,
        ]);

        if ($saved) {
            $this->answered_option_id = $option?->id;
        }
    }

    public function enviarRespuestaCorta(): void
    {
        if (! $this->canAnswer() || $this->current->question->qtype !== 'short') {
            return;
        }

        $this->validate([
            'respuesta' => 'required|string|max:255',
        ]);

        // Se guarda solo el texto: el docente califica después
        $saved = $this->storeAnswer([
            'question_option_id' => null,
            'is_correct' => false,
            'text' => $this->respuesta,
            'matched_id' => null,
            'score' => 0,
        ]);

        if ($saved) {
            $this->answered_option_id = null;
            $this->dispatch('respuesta_guardada', correcto: false, puntaje: 0);
        }
    }

    /** La partida corre, no está en pausa, hay pregunta y no ha vencido el tiempo (+ tolerancia). */
    private function canAnswer(): bool
    {
        if (! $this->current || ! $this->gameSession->is_running || $this->gameSession->is_paused) {
            return false;
        }

        $startAt = $this->gameSession->current_q_started_at;
        if ($startAt && $this->elapsedSeconds() > $this->timerSeconds() + self::GRACE_SECONDS) {
            return false;
        }

        return true;
    }

    private function timerSeconds(): int
    {
        return (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
    }

    private function elapsedSeconds(): float
    {
        $startAt = $this->gameSession->current_q_started_at;

        return $startAt ? max(0, $startAt->diffInRealSeconds(now(), false)) : 0;
    }

    /**
     * Guarda la respuesta (una sola por pregunta), recalcula totales del participante,
     * notifica al docente y pausa si ya respondieron todos los activos.
     */
    private function storeAnswer(array $attributes): bool
    {
        $timeMs = (int) min($this->timerSeconds() * 1000, $this->elapsedSeconds() * 1000);

        try {
            Answer::create($attributes + [
                'session_participant_id' => $this->me->id,
                'session_question_id' => $this->current->id,
                'time_ms' => $timeMs,
                'answered_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Doble clic / doble envío: ya existía la respuesta (índice único)
            $this->answered_any = true;

            return false;
        }

        $this->answered_any = true;
        $this->respuesta = '';

        $this->me->update(
            Answer::where('session_participant_id', $this->me->id)
                ->selectRaw('COALESCE(SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END),0) AS score, COALESCE(SUM(time_ms),0) AS time_total_ms')
                ->first()
                ->only(['score', 'time_total_ms'])
        );

        $this->notifyAndMaybeReveal();

        return true;
    }

    private function notifyAndMaybeReveal(): void
    {
        $activeIds = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('is_ignored', false)
            ->pluck('id');

        $pCount = $activeIds->count();
        $aCount = Answer::where('session_question_id', $this->current->id)
            ->whereIn('session_participant_id', $activeIds)
            ->count();

        // La respuesta ya está guardada: si Reverb falla, no debe romperle la pantalla al alumno
        rescue(fn () => AnswerSubmitted::dispatch($this->gameSession->id, $aCount, $pCount));

        if ($pCount > 0 && $aCount >= $pCount) {
            // Update condicional: solo un request "gana" la pausa (evita broadcasts duplicados)
            $paused = GameSession::whereKey($this->gameSession->id)
                ->where('is_paused', false)
                ->update(['is_paused' => true]);

            if ($paused) {
                $this->gameSession->refresh()->broadcastState($this->timerSeconds());
            }
        }
    }

    #[On('syncState')]
    public function syncState($payload = null)
    {
        $this->gameSession->refresh();
        $this->syncCurrent();
        $this->refreshRoster();
    }

    #[On('refreshStats')]
    public function refreshStats($payload = null)
    {
        $this->gameSession->refresh();
    }

    public function render()
    {
        $this->gameSession->refresh();

        if ($this->last_seen_index !== $this->gameSession->current_q_index) {
            $this->syncCurrent();
        }

        $secondsLeft = 0;
        if ($this->current && $this->gameSession->is_running && ! $this->gameSession->is_paused) {
            $secondsLeft = $this->gameSession->current_q_started_at
                ? (int) max(0, $this->timerSeconds() - $this->elapsedSeconds())
                : $this->timerSeconds();
        }

        return view('livewire.play-basic', [
            'secondsLeft' => $secondsLeft,
        ])->layout('layouts.adminlte', [
            'title' => 'Jugar',
            'header' => ($this->gameSession->title ?? 'Partida').' ['.$this->gameSession->code.']',
        ]);
    }
}
