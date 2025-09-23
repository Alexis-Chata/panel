<?php

namespace App\Livewire;

use App\Models\GameSession;
use App\Models\SessionQuestion;
use App\Models\SessionParticipant; // 👈 agrega esto
use Livewire\Component;

class ScreenDisplay extends Component
{
    public GameSession $gameSession;
    public ?SessionQuestion $current = null;
    private ?int $lastIndex = null;

    public function mount(GameSession $gameSession): void
    {
        $this->gameSession = $gameSession->fresh();
        $this->loadCurrent();
    }

    private function loadCurrent(): void
    {
        $idx = $this->gameSession->current_q_index;
        $this->current = $this->gameSession->sessionQuestions()
            ->with(['question.options' => fn($q) => $q->orderBy('opt_order')])
            ->where('q_order', $idx)->first();
        $this->lastIndex = $idx;
    }

    public function syncState(): void
    {
        $this->gameSession->refresh();
        if ($this->lastIndex !== $this->gameSession->current_q_index || !$this->current) {
            $this->loadCurrent();
        }
    }

    public function render()
    {
        $this->gameSession->refresh();
        if ($this->lastIndex !== $this->gameSession->current_q_index || !$this->current) {
            $this->loadCurrent();
        }

        // 👇 Top 3 (score desc, tiempo asc)
        $podium = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->take(3)
            ->get();

        return view('livewire.screen-display', compact('podium'))
            ->layout('layouts.fullscreen');
    }
}
