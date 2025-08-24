<?php

namespace App\Actions;

use App\Models\Answer;
use App\Models\AssignedQuestion;
use App\Models\Phase3Bonus;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Str;

class AnswerQuestion
{
    public function handle(AssignedQuestion $aq, ?QuestionOption $option, ?string $freeText, int $responseMs): Answer
    {
        $responseMs = max(0, (int)$responseMs);
        $isCorrect = $this->checkCorrect($aq->question, $option, $freeText);

        $ans = Answer::create([
            'assigned_question_id' => $aq->id,
            'question_option_id'   => $option?->id,
            'free_text'            => $freeText,
            'is_correct'           => $isCorrect,
            'response_ms'          => $responseMs,
            'answered_at'          => now(),
        ]);

        $settings = $aq->session->settings_json ?? [];
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

        // BONUS FASE 3 (top 3 más rápidos por pregunta)
        if ($aq->phase === 3 && $isCorrect) {
            $this->maybeAssignPhase3Bonus($aq);
        }

        // emitir evento realtime
        event(new \App\Events\ScoreUpdated($aq->game_session_id, $aq->participant_id));

        return $ans;
    }

    protected function checkCorrect(Question $q, ?QuestionOption $option, ?string $freeText): bool
    {
        return match ($q->type) {
            'single', 'boolean' => (bool)$option?->is_correct,
            'multi'            => false, // implementar validación multiselección
            'numeric'          => trim((string)$freeText) === data_get($q->meta, 'answer_numeric'),
            'text'             => Str::lower(trim($freeText)) === Str::lower(data_get($q->meta, 'answer_text')),
            default            => false,
        };
    }

    protected function maybeAssignPhase3Bonus(AssignedQuestion $aq): void
    {
        $sessionId = $aq->game_session_id;                  // ← usa la FK directa
        $settings  = $aq->session?->settings_json ?? [];    // ← opcional, para leer bonus desde la sesión si existe

        $bonus = [
            1 => data_get($settings, 'phase3.bonus_first', 3),
            2 => data_get($settings, 'phase3.bonus_second', 2),
            3 => data_get($settings, 'phase3.bonus_third', 1),
        ];

        // ¿Cuántos correctos ya tiene esa pregunta en esta sesión?
        $correctAlready = Phase3Bonus::where('game_session_id', $sessionId)
            ->where('question_id', $aq->question_id)->count();

        if ($correctAlready < 3) {
            $rank = $correctAlready + 1;
            Phase3Bonus::create([
                'game_session_id' => $sessionId,
                'question_id'     => $aq->question_id,
                'participant_id'  => $aq->participant_id,
                'rank'            => $rank,
                'points'          => $bonus[$rank] ?? 0,
            ]);

            if (($bonus[$rank] ?? 0) > 0) {
                $p = $aq->participant;
                $p->increment('phase3_score', $bonus[$rank]);
                $p->increment('total_score',  $bonus[$rank]);

                // (Opcional pero recomendado) emitir actualización de score por el bonus también:
                event(new \App\Events\ScoreUpdated($sessionId, $aq->participant_id));
            }
        }
    }
}
