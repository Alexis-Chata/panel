<?php

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\GameSessionPool;
use App\Models\QuestionPool;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear una partida en lobby
        $session = GameSession::firstOrCreate(
            ['code' => 'ABC123'],
            [
                'title' => 'Partida de Prueba',
                'status' => 'lobby',
                'current_phase' => 0,
                'phase1_count' => 10,
                'phase2_count' => 3,
                'phase3_count' => 10,
                'settings_json' => [
                    'phase1' => ['per_correct' => 1],
                    'phase2' => ['per_correct' => 2],
                    'phase3' => [
                        'per_correct' => 1,
                        'bonus_first' => 3,
                        'bonus_second' => 2,
                        'bonus_third' => 1,
                    ],
                ],
            ]
        );

        // Asociar pools a la sesión
        $p1 = QuestionPool::where('slug', 'evaluacion-inicial')->first();
        $p2 = QuestionPool::where('slug', 'versus-1v1')->first();
        $p3 = QuestionPool::where('slug', 'preguntas-rapidas')->first();

        foreach ([['phase1', $p1], ['phase2', $p2], ['phase3', $p3]] as [$phase, $pool]) {
            if ($pool) {
                GameSessionPool::firstOrCreate([
                    'game_session_id' => $session->id,
                    'question_pool_id' => $pool->id,
                    'phase' => $phase,
                ], ['weight' => 1]);
            }
        }

        // Agregar participantes (al menos 2 para fase 2)
        $students = User::where('email', 'like', 'student%@panel.test')->take(6)->get();
        foreach ($students as $u) {
            SessionParticipant::firstOrCreate(
                ['game_session_id' => $session->id, 'user_id' => $u->id],
                ['nickname' => $u->name, 'is_active' => true, 'joined_at' => now()]
            );
        }
    }
}
