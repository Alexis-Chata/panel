<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionShortAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $items = [
                [
                    'statement' => '¿Capital de Perú?',
                    'feedback'  => 'La capital es Lima.',
                    'qtype'     => 'short',
                    'meta'      => ['short_answer' => [
                        'case_sensitive' => false,
                        'strip_accents'  => true,
                        'max_distance'   => 1, // tolera 1 error (ej. "Lmia")
                    ]],
                    'answers'   => [
                        ['text' => 'Lima',              'weight' => 100],
                        ['text' => 'Ciudad de Lima',    'weight' => 100],
                        ['text' => 'Lima Metropolitana', 'weight' => 50],
                    ],
                ],
                [
                    'statement' => 'Escribe el símbolo químico del sodio.',
                    'feedback'  => 'Sodio = Na.',
                    'qtype'     => 'short',
                    'meta'      => ['short_answer' => [
                        'case_sensitive' => false,
                        'strip_accents'  => true,
                        'max_distance'   => 0, // exacta
                    ]],
                    'answers'   => [
                        ['text' => 'Na', 'weight' => 100],
                    ],
                ],
                [
                    'statement' => 'Lenguaje de programación creado por Rasmus Lerdorf.',
                    'feedback'  => 'PHP fue creado por Rasmus Lerdorf.',
                    'qtype'     => 'short',
                    'meta'      => ['short_answer' => [
                        'case_sensitive' => false,
                        'strip_accents'  => true,
                        'max_distance'   => 1,
                    ]],
                    'answers'   => [
                        ['text' => 'PHP',                     'weight' => 100],
                        ['text' => 'Hypertext Preprocessor',  'weight' => 50],
                    ],
                ],
            ];

            foreach ($items as $row) {
                // idempotente por enunciado
                $q = Question::updateOrCreate(
                    ['statement' => $row['statement']],
                    [
                        'feedback' => $row['feedback'],
                        'qtype'    => $row['qtype'],
                        'meta'     => $row['meta'],
                    ]
                );

                // limpiar y volver a cargar aceptadas
                $q->shortAnswers()->delete();
                $q->shortAnswers()->createMany($row['answers']);
            }
        });
    }
}
