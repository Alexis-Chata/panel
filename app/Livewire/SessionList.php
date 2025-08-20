<?php

namespace App\Livewire;

use App\Models\GameSession;
use Livewire\Component;

class SessionList extends Component
{
    public function render()
    {
        return view('livewire.session-list');
    }

    public $sessions;

    public function mount()
    {
        $this->sessions = GameSession::all();
    }

    public function changeStatus($sessionId, $status)
    {
        $session = GameSession::findOrFail($sessionId);
        $session->update(['status' => $status]);

        // Lanza la lógica de la fase correspondiente
        // (ej. PhaseOrchestrator::startPhaseX)
    }
}
