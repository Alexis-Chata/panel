<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        $rowNum = 1;

        foreach ($rows as $row) {
            $rowNum++;

            $statement = trim((string)($row['statement'] ?? ''));
            if ($statement === '') {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNum}: 'statement' es requerido.",
                ]);
            }

            $feedback = trim((string)($row['feedback'] ?? ''));
            $A = trim((string)($row['a'] ?? ''));
            $B = trim((string)($row['b'] ?? ''));
            $C = trim((string)($row['c'] ?? ''));
            $D = trim((string)($row['d'] ?? ''));
            $correct = strtoupper(trim((string)($row['correct'] ?? '')));

            if ($A === '' || $B === '' || $C === '' || $D === '') {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNum}: las columnas A,B,C,D son requeridas.",
                ]);
            }

            if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                throw ValidationException::withMessages([
                    'file' => "Fila {$rowNum}: 'correct' debe ser A, B, C o D.",
                ]);
            }

            $q = Question::create([
                'statement' => $statement,
                'feedback'  => $feedback ?: null,
            ]);

            $opts = [
                ['label' => 'A', 'content' => $A, 'is_correct' => $correct === 'A', 'opt_order' => 1],
                ['label' => 'B', 'content' => $B, 'is_correct' => $correct === 'B', 'opt_order' => 2],
                ['label' => 'C', 'content' => $C, 'is_correct' => $correct === 'C', 'opt_order' => 3],
                ['label' => 'D', 'content' => $D, 'is_correct' => $correct === 'D', 'opt_order' => 4],
            ];

            foreach ($opts as $o) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'label' => $o['label'],
                    'content' => $o['content'],
                    'is_correct' => $o['is_correct'],
                    'opt_order' => $o['opt_order'],
                ]);
            }
        }
    }

    public function headingRow(): int
    {
        return 1;
    }
}
