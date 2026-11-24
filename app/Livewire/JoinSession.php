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

        $lock = DeviceLock::firstOrCreate(
            ['game_session_id' => $session->id, 'user_id' => $user->id],
            ['device_hash' => $this->device_hash]
        );

        // Ya está vinculado a otro dispositivo: avisar aquí en vez de redirigir y rebotar
        if (! hash_equals((string) $lock->device_hash, (string) $this->device_hash)) {
            $this->error = 'Ya ingresaste a esta partida desde otro dispositivo. Pide al docente que lo libere si cambiaste de equipo.';

            return;
        }

        // Si ya existe participante, no duplicar
        $participant = SessionParticipant::firstOrCreate(
            ['game_session_id' => $session->id, 'user_id' => $user->id],
            ['nickname' => $user->name]
        );

        // Solo avisar a la sala cuando es un ingreso nuevo (no en cada reingreso)
        if ($participant->wasRecentlyCreated) {
            rescue(fn () => event(new ParticipantJoined(
                sessionId: $session->id,
                participant: [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'nickname' => $participant->nickname,
                    'score' => (int) $participant->score,
                ],
                total: $session->participants()->count()
            )));
        }

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
                'header' => '',
            ]);
    }
}
