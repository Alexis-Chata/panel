<?php

namespace App\Services;

use App\Models\AssignedQuestion;
use App\Models\GameSession;

class PhaseOrchestrator
{
    public function startPhase1(GameSession $s): void
    {
        $count = $s->phase1_count;
        $questions = app(PoolMixer::class)->pickForPhase($s, 'phase1', $count);

        foreach ($s->participants as $p) {
            $order = 1;
            foreach ($questions as $q) {
                AssignedQuestion::create([
                    'game_session_id' => $s->id,
                    'participant_id' => $p->id,
                    'question_id' => $q->id,
                    'phase' => 1,
                    'order' => $order++,
                ]);
            }
        }

        $s->update(['status' => 'phase1', 'current_phase' => 1]);
        event(new \App\Events\SessionPhaseChanged($s));
    }

    public function startPhase2(GameSession $s): void
    {
        app(Matchmaker::class)->make($s);

        $perPlayer = $s->phase2_count;
        $questions = app(PoolMixer::class)->pickForPhase($s, 'phase2', $perPlayer);

        foreach ($s->matches as $m) {
            foreach ([$m->player1_participant_id, $m->player2_participant_id] as $pid) {
                if (!$pid) continue; // bye
                $order = 1;
                foreach ($questions as $q) {
                    AssignedQuestion::create([
                        'game_session_id' => $s->id,
                        'participant_id' => $pid,
                        'question_id' => $q->id,
                        'match_id' => $m->id,
                        'phase' => 2,
                        'order' => $order++,
                    ]);
                }
            }
        }

        $s->update(['status' => 'phase2', 'current_phase' => 2]);
        event(new \App\Events\SessionPhaseChanged($s));
    }

    public function startPhase3(GameSession $s): void
    {
        $count = $s->phase3_count;
        $questions = app(PoolMixer::class)->pickForPhase($s, 'phase3', $count);

        // Fase 3: mismas preguntas para todos
        foreach ($questions as $idx => $q) {
            foreach ($s->participants as $p) {
                AssignedQuestion::create([
                    'game_session_id' => $s->id,
                    'participant_id' => $p->id,
                    'question_id' => $q->id,
                    'phase' => 3,
                    'order' => $idx + 1,
                ]);
            }
        }

        $s->update(['status' => 'phase3', 'current_phase' => 3]);
        event(new \App\Events\SessionPhaseChanged($s));
    }
}
