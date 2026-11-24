<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionQuestion;
use Illuminate\Support\Collection;

/**
 * Analítico por pregunta de una partida. Usado por el Excel y el PDF.
 * Siempre 2 consultas (preguntas+opciones y un agregado de respuestas), sin importar cuántas preguntas haya.
 */
class SessionAnalytics
{
    public const LABELS = ['A', 'B', 'C', 'D'];

    /** @return Collection<int, array{n:int,q:string,correct:string,answered:int,corrects:int,acc:float,A:int,B:int,C:int,D:int}> */
    public static function rows(GameSession|int $session, int $excerptWidth = 140): Collection
    {
        $sessionId = $session instanceof GameSession ? $session->id : $session;

        $sqs = SessionQuestion::where('game_session_id', $sessionId)
            ->with('question.options')
            ->orderBy('q_order')
            ->get();

        // Un solo agregado: por pregunta y alternativa -> total y correctas
        $agg = Answer::whereIn('session_question_id', $sqs->pluck('id'))
            ->selectRaw('session_question_id, question_option_id, COUNT(*) AS c, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS ok')
            ->groupBy('session_question_id', 'question_option_id')
            ->get()
            ->groupBy('session_question_id');

        return $sqs->values()->map(function (SessionQuestion $sq, int $i) use ($agg, $excerptWidth) {
            $rows = $agg->get($sq->id, collect());
            $byOption = $rows->whereNotNull('question_option_id')->pluck('c', 'question_option_id');

            $answered = (int) $rows->sum('c');
            $corrects = (int) $rows->sum('ok');

            $counts = array_fill_keys(self::LABELS, 0);
            $correctLabel = '';
            foreach ($sq->question->options->sortBy('opt_order') as $o) {
                $counts[$o->label] = (int) ($byOption[$o->id] ?? 0);
                if ($o->is_correct && $correctLabel === '') {
                    $correctLabel = $o->label;
                }
            }

            return [
                'n' => $i + 1,
                'q' => self::plainExcerpt($sq->question->statement, $excerptWidth),
                'correct' => $sq->question->qtype === 'short' ? 'Abierta' : $correctLabel,
                'answered' => $answered,
                'corrects' => $corrects,
                'acc' => $answered ? round($corrects * 100 / $answered, 1) : 0.0,
            ] + $counts;
        });
    }

    /** Texto plano (sin HTML de CKEditor) recortado. */
    public static function plainExcerpt(?string $html, int $width): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return mb_strimwidth($text, 0, $width, '…', 'UTF-8');
    }
}
