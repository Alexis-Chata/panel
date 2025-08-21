<?php

namespace App\Livewire\Admin;

use App\Models\GameMatch;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Services\PhaseOrchestrator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.adminlte-livewire')]
class SessionLobby extends Component
{
    public GameSession $session;

    public function mount(GameSession $session)
    {
        $this->session = $session->load('participants.user');
    }

    public function startPhase(int $n): void
    {
        $svc = app(PhaseOrchestrator::class);

        match ($n) {
            1 => $svc->startPhase1($this->session),
            2 => $svc->startPhase2($this->session),
            3 => $svc->startPhase3($this->session),
            default => null,
        };

        $this->refreshSession();
    }

    public function toResults(): void
    {
        $this->session->update(['status' => 'results', 'current_phase' => 0]);
        $this->session->refresh();
        event(new \App\Events\SessionPhaseChanged($this->session));
        $this->refreshSession();
    }

    public function finish(): void
    {
        $this->session->update(['status' => 'finished', 'current_phase' => 0]);
        $this->session->refresh();
        event(new \App\Events\SessionPhaseChanged($this->session));
        $this->refreshSession();
    }

    #[On('phase-changed')]
    #[On('score-updated')]
    public function refreshSession(): void
    {
        $this->session->refresh();
        $this->session->load('participants.user');
    }

    public function getRankingProperty()
    {
        return SessionParticipant::where('game_session_id', $this->session->id)
            ->orderByDesc('total_score')->orderBy('id')->get();
    }

    public function getMatchesProperty()
    {
        return GameMatch::with(['p1.user','p2.user','winner'])
            ->where('game_session_id', $this->session->id)
            ->orderBy('id')->get();
    }

    public function render()
    {
        return view('livewire.admin.session-lobby')->title("Partida — {$this->session->title}");
    }
}
