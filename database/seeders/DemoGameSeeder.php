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
        $total = min(10, \App\Models\Question::count());
        $session = GameSession::create([
            'code' => Str::upper(Str::random(6)),
            'title' => 'Demo Panel Básico',
            'phase_mode' => 'basic',
            'questions_total' => $total,
            'timer_default' => 25,
            'student_view_mode' => 'choices_only', // choices_only, full
            'is_active' => true,
            'is_running' => false,
            'current_q_index' => 0,
            'is_paused' => false,
            'starts_at' => now(),
        ]);

        $questions = Question::inRandomOrder()->take($total)->get();
        foreach ($questions as $i => $q) {
            SessionQuestion::create([
                'game_session_id' => $session->id,
                'question_id'     => $q->id,
                'q_order'         => $i,
            ]);
        }
    }
}
