<?php

namespace App\Livewire;

use App\Events\AnswerSubmitted;
use App\Events\GameSessionStateChanged;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PlayBasic extends Component
{
    public GameSession $gameSession;
    public ?SessionParticipant $me = null;
    public ?SessionQuestion $current = null;
    public ?int $answered_option_id = null; // opción marcada (si ya respondió)
    public ?int $last_seen_index = null;

    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession->fresh();
        $this->me = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->where('user_id', Auth::id())->first();

        abort_unless($this->me, 403, 'No estás unido a esta partida.');

        $this->syncCurrent();
    }

    public function syncCurrent(): void
    {
        $idx = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $idx)->first();

        $this->last_seen_index = $idx;

        if ($this->current) {
            $ans = Answer::where('session_participant_id', $this->me->id)
                ->where('session_question_id', $this->current->id)->first();

            $this->answered_option_id = $ans?->question_option_id;
        }
    }

    public function answer(?int $optionId, $ignoreElapsedFromClient = null)
    {
        // No aceptar si la partida no está corriendo o está en pausa
        if (!$this->gameSession->is_running || $this->gameSession->is_paused) {
            return;
        }

        // Evitar doble respuesta
        $exists = \App\Models\Answer::where('session_participant_id', $this->me->id)
            ->where('session_question_id', $this->current->id)->exists();
        if ($exists) return;

        // ===== TIEMPO DESDE SERVIDOR (robusto) =====
        $timerSec = (int)($this->current->timer_override ?? $this->gameSession->timer_default);
        $startAt  = $this->gameSession->current_q_started_at;

        // segundos transcurridos con signo; si es negativo, usa 0
        $elapsedSec = $startAt ? max(0, $startAt->diffInRealSeconds(now(), false)) : 0;

        // a milisegundos, clamp [0, timerSec*1000] y casteo a entero
        $serverElapsedMs = (int) min($timerSec * 1000, $elapsedSec * 1000);

        // ===========================================

        $option = $optionId ? $this->current->question->options->firstWhere('id', $optionId) : null;
        $isCorrect = $option ? (bool)$option->is_correct : false;

        \App\Models\Answer::create([
            'session_participant_id' => $this->me->id,
            'session_question_id'    => $this->current->id,
            'question_option_id'     => $optionId,
            'is_correct'             => $isCorrect,
            'time_ms'                => $serverElapsedMs,   // <-- SIEMPRE entero, no negativo
            'answered_at'            => now(),
        ]);

        // Recomputa score/tiempo del participante
        $sum = \App\Models\Answer::where('session_participant_id', $this->me->id);
        $this->me->update([
            'score'         => (clone $sum)->where('is_correct', true)->count(),
            'time_total_ms' => (clone $sum)->sum('time_ms'),
        ]);

        $this->answered_option_id = $optionId;

        $pCount = SessionParticipant::where('game_session_id', $this->gameSession->id)->count();
        $aCount = Answer::where('session_question_id', $this->current->id)->count();

        // Broadcast conteo
        AnswerSubmitted::dispatch($this->gameSession->id, $aCount, $pCount);

        // (Opcional) Revelar automáticamente
        if ($aCount >= $pCount) {
            $this->gameSession->update(['is_paused' => true]);
            GameSessionStateChanged::dispatch($this->gameSession->id, [
                'is_running' => true,
                'is_paused' => true,
                'current_q_index' => $this->gameSession->current_q_index,
                'current_q_started_at' => optional($this->gameSession->current_q_started_at)?->toIso8601String(),
            ]);
        }
    }

    #[On('syncState')]
    public function syncState($payload = null)
    {
        // Vuelve a cargar estado/cronómetro desde BD
        $this->gameSession->refresh();
        $this->syncCurrent();
    }

    #[On('refreshStats')]
    public function refreshStats($payload = null)
    {
        // Si quieres refrescar métricas locales o bloquear UI cuando $payload['answeredCount']==$payload['participantsCount']
        $this->gameSession->refresh();
    }

    public function render()
    {
        $this->gameSession->refresh();

        // Si el docente cambió de pregunta, resincroniza
        if ($this->last_seen_index !== $this->gameSession->current_q_index) {
            $this->syncCurrent();
        }

        // === SEGUNDOS RESTANTES CALCULADOS EN SERVER ===
        $secondsLeft = 0;
        if ($this->current && $this->gameSession->is_running && !$this->gameSession->is_paused) {
            $timerSec = (int) ($this->current->timer_override ?? $this->gameSession->timer_default);
            $startAt  = $this->gameSession->current_q_started_at;

            if ($startAt) {
                // tiempo transcurrido = (now - start) con signo; si es negativo, usamos 0
                $elapsed = max(0, $startAt->diffInRealSeconds(now(), false));
                $secondsLeft = max(0, $timerSec - $elapsed);
            } else {
                $secondsLeft = $timerSec;
            }
        }

        return view('livewire.play-basic', [
            'secondsLeft' => $secondsLeft,
        ])
            ->layout('layouts.adminlte', [
                'title'  => 'Jugar',
                'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
            ]);
    }
}
