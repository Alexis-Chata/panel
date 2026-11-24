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
        // Solo docentes con acceso o alumnos que participaron (el ranking muestra nombres)
        abort_unless($gameSession->isVisibleTo(auth()->user()), 403, 'No participaste en esta partida.');

        $this->gameSession = $gameSession;
    }

    public function render()
    {
        $ranking = SessionParticipant::where('game_session_id', $this->gameSession->id)
            ->with('user:id,name,email,profile_photo_path')
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
