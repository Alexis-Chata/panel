<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'statement' => '¿Capital de Perú?',
                'feedback'  => 'Lima es la capital y ciudad más poblada.',
                'options'   => [
                    ['label' => 'A', 'content' => 'Lima', 'is_correct' => true],
                    ['label' => 'B', 'content' => 'Cusco'],
                    ['label' => 'C', 'content' => 'Arequipa'],
                    ['label' => 'D', 'content' => 'Trujillo'],
                ],
            ],
            [
                'statement' => '¿Resultado de 7 × 8?',
                'feedback'  => '7×8 = 56.',
                'options'   => [
                    ['label' => 'A', 'content' => '54'],
                    ['label' => 'B', 'content' => '56', 'is_correct' => true],
                    ['label' => 'C', 'content' => '58'],
                    ['label' => 'D', 'content' => '60'],
                ],
            ],
            [
                'statement' => 'El agua hierve a… (a nivel del mar).',
                'feedback'  => '100 °C a 1 atm.',
                'options'   => [
                    ['label' => 'A', 'content' => '80 °C'],
                    ['label' => 'B', 'content' => '90 °C'],
                    ['label' => 'C', 'content' => '100 °C', 'is_correct' => true],
                    ['label' => 'D', 'content' => '120 °C'],
                ],
            ],
        ];

        foreach ($data as $qidx => $row) {
            $q = Question::create([
                'statement' => $row['statement'],
                'feedback'  => $row['feedback'] ?? null,
            ]);

            $order = 1;
            foreach ($row['options'] as $opt) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'label'       => $opt['label'],
                    'content'     => $opt['content'],
                    'is_correct'  => $opt['is_correct'] ?? false,
                    'opt_order'   => $order++,
                ]);
            }
        }
    }
}
