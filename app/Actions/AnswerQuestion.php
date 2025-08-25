<?php

namespace App\Actions;

use App\Models\Answer;
use App\Models\AssignedQuestion;
use App\Models\Phase3Bonus;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use Illuminate\Support\Str;

class AnswerQuestion
{
    public function handle(AssignedQuestion $aq, ?QuestionOption $option, ?string $freeText, int $responseMs): Answer
    {
        $isCorrect = $this->checkCorrect($aq->question, $option, $freeText);

        $ans = Answer::create([
            'assigned_question_id' => $aq->id,
            'question_option_id'   => $option?->id,
            'free_text'            => $freeText,
            'is_correct'           => $isCorrect,
            'response_ms'          => max(0, (int) $responseMs),
            'answered_at'          => now(),
        ]);

        $settings   = $aq->session?->settings_json ?? [];
        $perCorrect = match ($aq->phase) {
            1 => data_get($settings, 'phase1.per_correct', 1),
            2 => data_get($settings, 'phase2.per_correct', 2),
            3 => data_get($settings, 'phase3.per_correct', 1),
            default => 0,
        };

        if ($isCorrect && $perCorrect > 0) {
            $p = $aq->participant;
            if ($aq->phase === 1) $p->increment('phase1_score', $perCorrect);
            if ($aq->phase === 2) $p->increment('phase2_score', $perCorrect);
            if ($aq->phase === 3) $p->increment('phase3_score', $perCorrect);
            $p->increment('total_score', $perCorrect);
        }

        // BONUS FASE 3
        if ($aq->phase === 3 && $isCorrect) {
            $this->maybeAssignPhase3Bonus($aq);
        }

        // 👇 cierre automático del match en Fase 2 (y cálculo de ganador)
        if ($aq->phase === 2) {
            $this->maybeFinishMatch($aq);
        }

        // emitir evento realtime
        event(new \App\Events\ScoreUpdated($aq->game_session_id, $aq->participant_id));

        return $ans;
    }

    protected function checkCorrect(Question $q, ?QuestionOption $option, ?string $freeText): bool
    {
        return match ($q->type) {
            'single', 'boolean' => (bool)$option?->is_correct,
            'multi'             => false, // TODO multiselección
            'numeric'           => trim((string)$freeText) === data_get($q->meta, 'answer_numeric'),
            'text'              => Str::lower(trim($freeText)) === Str::lower(data_get($q->meta, 'answer_text')),
            default             => false,
        };
    }

    protected function maybeAssignPhase3Bonus(AssignedQuestion $aq): void
    {
        $session  = $aq->session;
        $settings = $session->settings_json ?? [];
        $bonus    = [
            1 => data_get($settings, 'phase3.bonus_first', 3),
            2 => data_get($settings, 'phase3.bonus_second', 2),
            3 => data_get($settings, 'phase3.bonus_third', 1),
        ];

        $correctAlready = Phase3Bonus::where('game_session_id', $session->id)
            ->where('question_id', $aq->question_id)->count();

        if ($correctAlready < 3) {
            $rank = $correctAlready + 1;
            Phase3Bonus::create([
                'game_session_id' => $session->id,
                'question_id'     => $aq->question_id,
                'participant_id'  => $aq->participant_id,
                'rank'            => $rank,
                'points'          => $bonus[$rank] ?? 0,
            ]);

            if ($bonus[$rank] ?? 0) {
                $p = $aq->participant;
                $p->increment('phase3_score', $bonus[$rank]);
                $p->increment('total_score',  $bonus[$rank]);

                // (Opcional pero recomendado) emitir actualización de score por el bonus también:
                //event(new \App\Events\ScoreUpdated($session->id, $aq->participant_id));
            }
        }
    }

    /**
     * Cierra el match de Fase 2 cuando ambos jugadores terminaron sus N preguntas.
     * Calcula y asigna ganador (empate => null). Para "bye", ganador null.
     */
    protected function maybeFinishMatch(AssignedQuestion $aq): void
    {
        $match = $aq->match;
        if (!$match) return;

        // Evitar trabajo repetido si ya está terminado
        if ($match->status === 'finished') return;

        // Obtén la sesión de forma segura
        $session = $aq->session ?: GameSession::find($aq->game_session_id);
        if (!$session) return;

        $perPlayer = (int) ($session->phase2_count ?? 3);

        // helper para contar respuestas de un participante en este match
        $countAnswers = function (?int $participantId) use ($session, $match): int {
            if (!$participantId) return 0;
            return AssignedQuestion::where('game_session_id', $session->id)
                ->where('phase', 2)
                ->where('game_match_id', $match->id)
                ->where('participant_id', $participantId)
                ->whereHas('answer')
                ->count();
        };

        $p1Id = $match->player1_participant_id;
        $p2Id = $match->player2_participant_id;

        $p1Done = $p1Id ? ($countAnswers($p1Id) >= $perPlayer) : false;
        $p2Done = $p2Id ? ($countAnswers($p2Id) >= $perPlayer) : true; // si no hay p2 (bye), trátalo como hecho

        // Si ambos (o el único, en bye) terminaron, cerramos el match
        if ($p1Done && $p2Done) {
            $winnerId = null;

            if ($p1Id && $p2Id) {
                $p1 = SessionParticipant::find($p1Id);
                $p2 = SessionParticipant::find($p2Id);

                $p1Score = (int) ($p1?->phase2_score ?? 0);
                $p2Score = (int) ($p2?->phase2_score ?? 0);

                if ($p1Score > $p2Score) $winnerId = $p1Id;
                elseif ($p2Score > $p1Score) $winnerId = $p2Id;
                else $winnerId = null; // empate
            }
            // En "bye" → winnerId queda null (muestra "-")

            $match->update([
                'status'                 => 'finished',
                'ends_at'                => now(),
                'winner_participant_id'  => $winnerId,
            ]);
        }
    }
}
