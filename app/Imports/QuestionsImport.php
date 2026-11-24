<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa preguntas de alternativa (A-D) a una categoría concreta.
 * Valida TODAS las filas antes de guardar y guarda en una transacción: o entran todas o ninguna.
 */
class QuestionsImport implements ToCollection, WithHeadingRow
{
    public const LABELS = ['A', 'B', 'C', 'D'];

    public int $imported = 0;

    /**
     * @param  int  $groupId  Categoría destino (question_groups.id, obligatoria en BD)
     * @param  int|null  $ownerId  Dueño de las preguntas (null = institucional), igual que la categoría
     */
    public function __construct(protected int $groupId, protected ?int $ownerId = null) {}

    public function collection(Collection $rows)
    {
        $parsed = [];
        $errors = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // fila 1 = encabezados

            $statement = trim((string) ($row['statement'] ?? ''));
            $options = [];
            foreach (self::LABELS as $label) {
                $options[$label] = trim((string) ($row[strtolower($label)] ?? ''));
            }
            $correct = strtoupper(trim((string) ($row['correct'] ?? '')));

            // Fila totalmente vacía (frecuente al final de un Excel): se ignora
            if ($statement === '' && implode('', $options) === '' && $correct === '') {
                continue;
            }

            if ($statement === '') {
                $errors[] = "Fila {$rowNum}: 'statement' es requerido.";
            }
            if (in_array('', $options, true)) {
                $errors[] = "Fila {$rowNum}: las columnas A, B, C y D son requeridas.";
            }
            if (! in_array($correct, self::LABELS, true)) {
                $errors[] = "Fila {$rowNum}: 'correct' debe ser A, B, C o D.";
            }

            $parsed[] = [
                'statement' => $statement,
                'feedback' => trim((string) ($row['feedback'] ?? '')) ?: null,
                'options' => $options,
                'correct' => $correct,
            ];
        }

        if ($errors) {
            // Se muestran como máximo 5 errores para no saturar la pantalla
            $extra = count($errors) > 5 ? ' (y '.(count($errors) - 5).' error(es) más)' : '';
            throw ValidationException::withMessages(['file' => implode(' ', array_slice($errors, 0, 5)).$extra]);
        }

        if ($parsed === []) {
            throw ValidationException::withMessages(['file' => 'El archivo no tiene filas para importar.']);
        }

        DB::transaction(function () use ($parsed) {
            $now = now();
            foreach ($parsed as $p) {
                $q = Question::create([
                    'statement' => $p['statement'],
                    'feedback' => $p['feedback'],
                    'qtype' => 'multiple',
                    'question_group_id' => $this->groupId,
                    'user_id' => $this->ownerId,
                ]);

                $opts = [];
                foreach (self::LABELS as $order => $label) {
                    $opts[] = [
                        'question_id' => $q->id,
                        'label' => $label,
                        'content' => $p['options'][$label],
                        'is_correct' => $p['correct'] === $label,
                        'opt_order' => $order + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                QuestionOption::insert($opts);
            }
        });

        $this->imported = count($parsed);
    }

    public function headingRow(): int
    {
        return 1;
    }
}
