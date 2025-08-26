<?php

namespace App\Services;

use App\Events\SessionPhaseChanged;
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

        $duration = (int) data_get($s->settings_json, 'phase1.duration_seconds', 180); // 3min por defecto
        $endsAt   = now()->addSeconds($duration);

        $s->update(['status' => 'phase1', 'current_phase' => 1, 'phase_ends_at' => $endsAt,]);

        // programa el cierre automático
        \App\Jobs\EndPhaseIfDue::dispatch($s->id, 'phase1')->delay($endsAt->clone()->addSecond());

        event(new SessionPhaseChanged($s));
    }

    public function startPhase2(GameSession $s): void
    {
        app(Matchmaker::class)->make($s);

        $perPlayer = (int) $s->phase2_count;
        $questions = app(PoolMixer::class)->pickForPhase($s, 'phase2', $perPlayer);

        // Asegúrate de tener los matches recién creados
        $s->load('matches');

        foreach ($s->matches as $m) {
            // Asignar preguntas a p1 y p2 (si existe)
            foreach ([$m->player1_participant_id, $m->player2_participant_id] as $pid) {
                if (!$pid) continue; // bye
                $order = 1;
                foreach ($questions as $q) {
                    AssignedQuestion::create([
                        'game_session_id' => $s->id,
                        'participant_id' => $pid,
                        'question_id' => $q->id,
                        'game_match_id' => $m->id,
                        'phase' => 2,
                        'order' => $order++,
                    ]);
                }
            }
            // Cambiar estado del match a 'active'
            $m->update([
                'status'    => 'active',
                'starts_at' => now(),
            ]);
        }

        $duration = (int) data_get($s->settings_json, 'phase2.duration_seconds', 120); // 2min por defecto
        $endsAt   = now()->addSeconds($duration);

        $s->update(['status' => 'phase2', 'current_phase' => 2, 'phase_ends_at' => $endsAt]);

        \App\Jobs\EndPhaseIfDue::dispatch($s->id, 'phase2')->delay($endsAt->clone()->addSecond());

        event(new SessionPhaseChanged($s));
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

        $duration = (int) data_get($s->settings_json, 'phase3.duration_seconds', 150); // 2:30 por defecto
        $endsAt   = now()->addSeconds($duration);

        $s->update(['status' => 'phase3', 'current_phase' => 3, 'phase_ends_at' => $endsAt]);

        \App\Jobs\EndPhaseIfDue::dispatch($s->id, 'phase3')->delay($endsAt->clone()->addSecond());

        event(new SessionPhaseChanged($s));
    }
}
