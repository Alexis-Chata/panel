<?php

namespace App\Livewire;

use App\Events\ParticipantJoined;
use App\Models\DeviceLock;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class JoinSession extends Component
{
    public string $code = '';
    public ?string $device_hash = null;
    public ?string $error = null;

    public function mount(Request $request)
    {
        // Prioridad 1: parámetro de query ?code=...
        if ($request->filled('code')) {
            $this->code = strtoupper(trim($request->query('code')));
        } else {
            // Prioridad 2: partida activa
            $active = GameSession::where('is_active', true)->latest()->first();
            if ($active) {
                $this->code = $active->code;
            }
        }
    }

    public function join()
    {
        $this->error = null;
        $user = Auth::user();
        $session = GameSession::where('code', strtoupper(trim($this->code)))->first();

        if (!$session || !$session->is_active) {
            $this->error = 'Código inválido o la partida no está activa.';
            return;
        }

        // Verificar device lock
        if (!$this->device_hash) {
            $this->error = 'No se detectó dispositivo. Reintenta.';
            return;
        }

        DeviceLock::firstOrCreate(
            ['game_session_id' => $session->id, 'user_id' => $user->id],
            ['device_hash' => $this->device_hash]
        );

        // Si ya existe participante, no duplicar
        $participant = SessionParticipant::firstOrCreate(
            ['game_session_id' => $session->id, 'user_id' => $user->id],
            ['nickname' => $user->name]
        );

        // Total actualizado de participantes
        $total = $session->participants()->count();

        // Dispara el broadcast
        event(new ParticipantJoined(
            sessionId: $session->id,
            participant: [
                'id'       => $participant->id,
                'user_id'  => $participant->user_id,
                'nickname' => $participant->nickname,
                'score'    => $participant->total_score,
            ],
            total: $total
        ));

        Cookie::queue(
            'panel_device_hash_' . $session->id,
            $this->device_hash,
            60 * 24 * 14 // 14 días
        );

        return $this->redirectRoute('play', ['gameSession' => $session->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.join-session')
            ->layout('layouts.adminlte', [
                'title' => 'Unirse a partida',
                'header' => 'Unirse a una partida',
            ]);
    }
}
