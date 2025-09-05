<?php

namespace App\Exports;

use App\Models\Answer;
use App\Models\SessionQuestion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GameSessionAnalyticsExport implements FromCollection, WithHeadings
{
    public function __construct(protected int $gameSessionId) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $rows = collect();

        $sqs = SessionQuestion::where('game_session_id', $this->gameSessionId)
            ->with('question.options')
            ->orderBy('q_order')
            ->get();

        foreach ($sqs as $i => $sq) {
            $opts = $sq->question->options->sortBy('opt_order')->values();

            // Inicializa distribución A-D
            $labels = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            $isCorrectByLabel = [];
            foreach ($opts as $o) {
                $isCorrectByLabel[$o->label] = (bool) $o->is_correct;
            }

            // Conteo por opción
            $dist = Answer::selectRaw('question_option_id, COUNT(*) as c')
                ->where('session_question_id', $sq->id)
                ->whereNotNull('question_option_id')
                ->groupBy('question_option_id')
                ->pluck('c', 'question_option_id');

            foreach ($opts as $o) {
                $labels[$o->label] = (int) ($dist[$o->id] ?? 0);
            }

            $answered = Answer::where('session_question_id', $sq->id)->count();
            $corrects = Answer::where('session_question_id', $sq->id)
                ->where('is_correct', true)->count();
            $acc = $answered ? round(($corrects * 100) / $answered, 1) : 0.0;

            $questionText = $sq->question->statement;
            $excerpt = mb_strimwidth($questionText, 0, 120, '…', 'UTF-8');

            $correctLabel = array_search(true, $isCorrectByLabel, true) ?: '';

            $rows->push([
                'N°'          => $i + 1,
                'Pregunta'    => $excerpt,
                'Correcta'    => $correctLabel,
                'Respondidos' => $answered,
                'Correctos'   => $corrects,
                '% Acierto'   => $acc,
                'A'           => $labels['A'],
                'B'           => $labels['B'],
                'C'           => $labels['C'],
                'D'           => $labels['D'],
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['N°', 'Pregunta', 'Correcta', 'Respondidos', 'Correctos', '% Acierto', 'A', 'B', 'C', 'D'];
    }
}
