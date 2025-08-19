<?php

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\GameSessionPool;
use App\Models\QuestionPool;
use App\Models\SessionParticipant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GameSession::factory()
        ->count(3)
        ->create()
        ->each(function ($session) {
            SessionParticipant::factory()
                ->count(rand(4,10))
                ->for($session)
                ->create();

            // Añade algunas pools de preguntas (para fase1,2,3)
            foreach (['phase1','phase2','phase3'] as $phase) {
                GameSessionPool::create([
                    'game_session_id' => $session->id,
                    'question_pool_id' => QuestionPool::inRandomOrder()->first()->id,
                    'phase' => $phase,
                    'weight' => rand(1,3),
                ]);
            }
        });
    }
}
