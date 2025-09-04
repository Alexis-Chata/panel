<?php

namespace App\Http\Middleware;

use App\Models\DeviceLock;
use App\Models\GameSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $param = $request->route('gameSession');

        // Soporta tanto ID como el objeto ya vinculado
        $session = $param instanceof GameSession ? $param : GameSession::find($param);
        if (!$session) {
            return redirect()
                ->route('join')
                ->with('error', 'La partida no existe o fue cerrada.');
        }

        $cookieKey = 'panel_device_hash_' . $session->id;
        $hashCookie = $request->cookie($cookieKey);
        if (!$hashCookie) {
            return redirect()
                ->route('join')
                ->with('error', 'Dispositivo no verificado. Ingresa a la partida con el código.');
        }

        $lock = DeviceLock::where('game_session_id', $session->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$lock || !hash_equals($lock->device_hash, $hashCookie)) {
            return redirect()
                ->route('join')
                ->with('error', 'Este dispositivo no está autorizado para esa partida.');
        }

        // Asegura que Livewire reciba el modelo resuelto
        $request->route()->setParameter('gameSession', $session);

        return $next($request);
    }
}
