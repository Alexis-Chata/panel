<?php

namespace App\Livewire;

use App\Models\GameSession;
use App\Models\SessionParticipant;
use Livewire\Component;

class WinnersView extends Component
{
    public GameSession $gameSession;

    public function mount(GameSession $gameSession)
    {
        $this->gameSession = $gameSession;
    }

    public function render()
    {
        $ranking = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->take(10)
            ->get();

        return view('livewire.winners-view', compact('ranking'))
            ->layout('layouts.adminlte', [
                'title' => 'Ganadores',
                'header' => 'Ganadores de la partida',
            ]);
    }
}
