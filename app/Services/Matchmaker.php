<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\GameSession;

class Matchmaker
{
    public function make(GameSession $session): void
    {
        $players = $session->participants()->where('is_active', true)
            ->orderBy('phase1_score') // menor puntaje primero (para bye si impar)
            ->get();

        $bye = null;
        if ($players->count() % 2 === 1) {
            $bye = $players->shift(); // saca al menor puntaje
            GameMatch::create([
                'game_session_id' => $session->id,
                'player1_participant_id' => $bye->id,
                'player2_participant_id' => null,
                'status' => 'pending',
            ]);
        }

        $shuffled = $players->shuffle()->values();
        for ($i = 0; $i < $shuffled->count(); $i += 2) {
            GameMatch::create([
                'game_session_id' => $session->id,
                'player1_participant_id' => $shuffled[$i]->id,
                'player2_participant_id' => $shuffled[$i + 1]->id,
                'status' => 'pending',
            ]);
        }
    }
}
