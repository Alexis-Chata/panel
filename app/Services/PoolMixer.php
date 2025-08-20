<?php

namespace App\Services;

use App\Models\GameSession;
use Illuminate\Support\Collection;

class PoolMixer
{
    public function pickForPhase(GameSession $session, string $phase, int $count): Collection
    {
        $pools = $session->pools()->where('phase',$phase)->with('questionPool.questions')->get();
        $bag = collect();

        foreach ($pools as $sp) {
            // duplica el questionPool según weight
            for ($i=0; $i<$sp->weight; $i++) {
                $bag = $bag->merge($sp->questionPool->questions);
            }
        }

        return $bag->shuffle()->unique('id')->take($count);
    }
}
