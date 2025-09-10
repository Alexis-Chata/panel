<?php

use App\Models\GameSession;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    // Docente/Admin pueden oír siempre
    if ($user->can('sessions.run')) return ['id' => $user->id, 'name' => $user->name];

    // Estudiante: debe estar inscrito en la sesión
    return SessionParticipant::where('game_session_id', $sessionId)
        ->where('user_id', $user->id)->exists()
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

Broadcast::channel('game-session.{session}', function ($user, GameSession $session) {
    // Autoriza quién puede estar en la partida.
    // Para MVP, puedes permitir a cualquier autenticado:
    // if (! $user) return false;

    // Si ya guardas participantes por user_id, puedes validar así:
    // $allowed = $session->participants()->where('user_id', $user->id)->exists();
    // if (! $allowed) return false;

    // Datos que verán los demás en el presence (aquí puedes retornar nickname/avatares)
    return [
        'id'       => $user->id,
        'name'     => $user->name,
        'avatar'   => $user->profile_photo_url ?? null,
    ];
});
