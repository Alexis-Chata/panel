<?php

namespace App\Livewire;

use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Livewire\Component;

class RunSession extends Component
{
    public GameSession $gameSession;
    public ?SessionQuestion $current = null;

    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession->fresh();
        $this->loadCurrent();
    }

    public function loadCurrent(): void
    {
        $index = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with('question.options')
            ->where('q_order', $index)->first();
    }

    public function start(): void
    {
        $this->gameSession->update(['is_running' => true, 'is_paused' => false, 'current_q_started_at' => now(),]);
        $this->dispatch('toast', body: 'Partida iniciada');
    }

    public function togglePause(): void
    {
        $this->gameSession->update(['is_paused' => !$this->gameSession->is_paused]);
        $this->gameSession->refresh();
    }

    /** Revela la respuesta correcta (pausa para explicación) */
    public function revealAndPause(): void
    {
        $this->gameSession->update(['is_paused' => true]);
        // No guardamos un flag aparte: los alumnos determinan correcta leyendo la opción correcta
        $this->dispatch('toast', body: 'Respuesta revelada y pausa activa');
    }

    /** Avanza a la siguiente pregunta con conteo 3-2-1 */
    public function nextQuestion(): void
    {
        $next = $this->gameSession->current_q_index + 1;

        if ($next >= $this->gameSession->questions_total) {
            // Fin de la partida
            $this->gameSession->update([
                'is_running' => false,
                'is_paused' => false,
            ]);
            $this->redirectRoute('winners', ['gameSession' => $this->gameSession->id], navigate: true);
            return;
        }

        $this->dispatch('countdown'); // JS: muestra 3-2-1

        // Pequeño delay: al final del conteo, cambiar índice
        $this->dispatch('advance-after-count');
    }

    /** Listener desde JS tras el conteo */
    #[\Livewire\Attributes\On('advanceNow')]
    public function advanceNow(): void
    {
        $this->gameSession->increment('current_q_index');
        $this->gameSession->update(['is_paused' => false, 'is_running' => true, 'current_q_started_at' => now(),]);
        $this->gameSession->refresh();
        $this->loadCurrent();
    }

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


    public function render()
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        $pCount   = $this->participantsCount();
        $answered = $this->answeredCount();
        $corrects = $this->correctCount();
        $dist     = $this->optionDistribution();
        $top      = $this->liveTop();

        return view('livewire.run-session', compact('pCount', 'answered', 'corrects', 'dist', 'top'))
            ->layout('layouts.adminlte', [
                'title' => 'Ejecutar Partida',
                'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
            ]);
    }
}
