<?php

namespace App\Livewire;

use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Illuminate\Support\Facades\Auth;
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

    /** Responder (desde el front mandamos elapsedMs) */
    public function answer(?int $optionId, int $elapsedMs)
    {
        // Validación server-side de ventana de tiempo
        $timerSec = (int)($this->current->timer_override ?? $this->gameSession->timer_default);
        $startAt  = $this->gameSession->current_q_started_at;

        if ($startAt) {
            $deadline = \Illuminate\Support\Carbon::parse($startAt)->addSeconds($timerSec);
            if (now()->greaterThan($deadline)) {
                // Tiempo agotado en server: no aceptar respuesta
                return;
            }
        }
        // Evitar doble respuesta
        $exists = Answer::where('session_participant_id', $this->me->id)
            ->where('session_question_id', $this->current->id)->exists();

        if ($exists) return;

        $option = $optionId ? $this->current->question->options->firstWhere('id', $optionId) : null;
        $isCorrect = $option ? (bool)$option->is_correct : false;

        Answer::create([
            'session_participant_id' => $this->me->id,
            'session_question_id' => $this->current->id,
            'question_option_id' => $optionId,
            'is_correct' => $isCorrect,
            'time_ms' => max(0, (int)$elapsedMs),
            'answered_at' => now(),
        ]);

        // Recomputa score/tiempo del participante (simple y suficiente)
        $sum = Answer::where('session_participant_id', $this->me->id);
        $this->me->update([
            'score' => (clone $sum)->where('is_correct', true)->count(),
            'time_total_ms' => (clone $sum)->sum('time_ms'),
        ]);

        $this->answered_option_id = $optionId;
    }

    public function render()
    {
        $this->gameSession->refresh();

        // Si el docente avanzó, resync
        if ($this->last_seen_index !== $this->gameSession->current_q_index) {
            $this->syncCurrent();
        }

        return view('livewire.play-basic')
            ->layout('layouts.adminlte', [
                'title' => 'Jugar',
                'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
            ]);
    }
}
