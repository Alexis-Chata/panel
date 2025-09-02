<?php

namespace Database\Seeders;

use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionQuestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $session = GameSession::create([
            'code' => Str::upper(Str::random(6)),
            'title' => 'Demo Panel Básico',
            'phase_mode' => 'basic',
            'questions_total' => 10,
            'timer_default' => 25,
            'student_view_mode' => 'full',
            'is_active' => true,
            'is_running' => false,
            'current_q_index' => 0,
            'is_paused' => false,
            'starts_at' => now(),
        ]);

        $questions = Question::inRandomOrder()->take(10)->get();
        $i = 0;
        foreach ($questions as $q) {
            SessionQuestion::create([
                'game_session_id' => $session->id,
                'question_id' => $q->id,
                'q_order' => $i++,
            ]);
        }
    }
}
