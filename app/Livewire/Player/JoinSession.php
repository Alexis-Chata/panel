<?php

namespace App\Livewire\Player;

use App\Events\ParticipantUpdated;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.adminlte-livewire')]
#[Title('Unirse a una partida')]
class JoinSession extends Component
{
    public function render()
    {
        return view('livewire.player.join-session');
    }

    public string $code = '';
    public ?string $error = null;

    public function join(): void
    {
        $this->error = null;

        $game_session = GameSession::query()
            ->where('code', trim($this->code))
            ->whereIn('status', ['lobby', 'phase1', 'phase2', 'phase3'])
            ->first();

        if (!$game_session) {
            $this->error = 'Código inválido o partida no disponible.';
            return;
        }

        $participant = SessionParticipant::firstOrCreate(
            ['game_session_id' => $game_session->id, 'user_id' => Auth::id()],
            ['nickname' => Auth::user()->name, 'is_active' => true, 'joined_at' => now()]
        );

        event(new ParticipantUpdated($participant, 'joined'));

        redirect()->route('player.play', ['session' => $game_session->id]);
    }
}
