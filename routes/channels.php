<?php

use App\Models\GameSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privado de la partida (estado, conteo, respuestas).
 * Solo el docente con acceso a ESA partida (dueño/Admin/sin dueño) o un alumno inscrito.
 */
Broadcast::channel('session.{session}', function ($user, GameSession $session) {
    return $session->isVisibleTo($user)
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

/**
 * Canal presence de la sala de espera. Antes autorizaba a cualquier usuario autenticado:
 * cualquiera podía "aparecer" en la partida y ver la lista de presentes.
 */
Broadcast::channel('game-session.{session}', function ($user, GameSession $session) {
    if (! $session->isVisibleTo($user)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->profile_photo_url ?? null,
    ];
});
