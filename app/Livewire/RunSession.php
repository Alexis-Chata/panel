<?php

namespace App\Livewire;

use App\Models\GameSession;
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
        $this->gameSession->update(['is_running' => true, 'is_paused' => false]);
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
        $this->gameSession->update(['is_paused' => false, 'is_running' => true]);
        $this->gameSession->refresh();
        $this->loadCurrent();
    }

    public function render()
    {
        $this->gameSession->refresh();
        $this->loadCurrent();

        return view('livewire.run-session')
            ->layout('layouts.adminlte', [
                'title' => 'Ejecutar Partida',
                'header' => ($this->gameSession->title ?? 'Partida') . ' [' . $this->gameSession->code . ']',
            ]);
    }
}
