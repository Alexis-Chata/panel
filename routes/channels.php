<?php

use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('sessions.{sessionId}.scores', function (User $user, int $sessionId) {
    return SessionParticipant::where('game_session_id', $sessionId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('sessions.{sessionId}.phase', function (User $user, int $sessionId) {
    return SessionParticipant::where('game_session_id', $sessionId)
        ->where('user_id', $user->id)
        ->exists();
});
