<?php

namespace App\Livewire\Board;

use App\Models\GameSession;
use App\Models\SessionParticipant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.adminlte-livewire')]
#[Title('Pizarra en vivo')]
class Leaderboard extends Component
{
    public GameSession $session;
    public int $limit = 10;

    public function mount(GameSession $session)
    {
        $this->session = $session;
    }

    #[On('score-updated')]
    #[On('phase-changed')]
    public function refreshBoard(): void
    {
        $this->session->refresh();
    }

    public function getTopProperty()
    {
        return SessionParticipant::where('game_session_id', $this->session->id)
            ->orderByDesc('total_score')
            ->orderBy('id')
            ->take($this->limit)
            ->get();
    }

    public function render()
    {
        return view('livewire.board.leaderboard')
            ->title("Pizarra — {$this->session->title}");
    }
}
