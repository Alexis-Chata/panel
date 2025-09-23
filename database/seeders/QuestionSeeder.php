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
        // =========
        // 1) LOTE BASE (15 preguntas de cultura general)
        // =========
        $base =
        [
            [
                'statement' => '¿Cuántos Huesos tiene el ser Humano Adulto?',
                'feedback'  => 'El cuerpo humano adulto tiene 206 huesos, ya que varios huesos que están separados al nacer se fusionan con el crecimiento.',
                'correct'   => 'B',
                'A' => '302',
                'B' => '206',
                'C' => '208',
                'D' => '306',
            ],
            [
                'statement' => '¿Cuántos Huesos tiene un bebe al nacer?',
                'feedback'  => 'Un bebé nace con alrededor de 300 huesos, pero muchos se van uniendo con el desarrollo hasta quedar en 206 en la adultez.',
                'correct'   => 'A',
                'A' => '300',
                'B' => '206',
                'C' => '196',
                'D' => '192',
            ],
            [
                'statement' => '¿Cuántos océanos hay en el planeta tierra?',
                'feedback'  => 'Actualmente se reconocen 5 océanos: Pacífico, Atlántico, Índico, Ártico y Antártico.',
                'correct'   => 'C',
                'A' => '6',
                'B' => '4',
                'C' => '5',
                'D' => '7',
            ],
            [
                'statement' => '¿Cuál es la estrella más cercana a la tierra?',
                'feedback'  => 'El Sol es la estrella más cercana a la Tierra y la fuente principal de luz y energía para la vida.',
                'correct'   => 'B',
                'A' => 'Andromeda',
                'B' => 'Sol',
                'C' => 'Luna',
                'D' => 'Ninguno',
            ],
            [
                'statement' => '¿Cuántos Kilos hay en una Tonelada?',
                'feedback'  => 'Una tonelada equivale a 1.000 kilogramos.',
                'correct'   => 'A',
                'A' => '1.000',
                'B' => '100',
                'C' => '10.000',
                'D' => '1.500',
            ],
            [
                'statement' => '¿Cuál es el océano más grande?',
                'feedback'  => 'El océano Pacífico es el más grande del planeta, cubriendo más superficie que todos los continentes juntos.',
                'correct'   => 'A',
                'A' => 'Pacífico',
                'B' => 'Atlántico',
                'C' => 'Índico',
                'D' => 'Ártico',
            ],
            [
                'statement' => '¿Cuál es el planeta más cercano al Sol?',
                'feedback'  => 'Mercurio es el planeta más cercano al Sol y también el más pequeño del sistema solar.',
                'correct'   => 'D',
                'A' => 'Venus',
                'B' => 'Tierra',
                'C' => 'Marte',
                'D' => 'Mercurio',
            ],
            [
                'statement' => '¿Cuál es el nombre de la galaxia donde vivimos?',
                'feedback'  => 'Vivimos en la galaxia llamada Vía Láctea, donde también se encuentra nuestro sistema solar.',
                'correct'   => 'C',
                'A' => 'Andromeda',
                'B' => 'Pegaso',
                'C' => 'Vía Láctea',
                'D' => 'Osa Mayor',
            ],
            [
                'statement' => '¿Quién pintó “La Gioconda”?',
                'feedback'  => 'La Gioconda, también conocida como Mona Lisa, fue pintada por Leonardo da Vinci en el siglo XVI.',
                'correct'   => 'A',
                'A' => 'Leonardo da Vinci',
                'B' => 'Miguel Ángel',
                'C' => 'Rafael',
                'D' => 'Botticelli',
            ],
            [
                'statement' => '¿Cómo se llama el movimiento que realiza el Corazón”?',
                'feedback'  => 'es Latido',
                'correct'   => 'C',
                'A' => 'Sistole',
                'B' => 'Diástole',
                'C' => 'Latido',
                'D' => 'pam pam',
            ],
        ];


        foreach ($base as $row) {
            $this->storeQuestion($row);
        }
    }

    /** Guarda una pregunta con sus 4 opciones (A–D), idempotente por 'statement'. */
    private function storeQuestion(array $row): void
    {
        $q = Question::firstOrCreate(
            ['statement' => $row['statement']],
            ['feedback'  => $row['feedback'] ?? null],
        );

        // Reescribir opciones para asegurar exactamente 1 correcta
        $q->options()->delete();

        $map = ['A', 'B', 'C', 'D'];
        $order = 1;
        foreach ($map as $label) {
            QuestionOption::create([
                'question_id' => $q->id,
                'label'       => $label,
                'content'     => (string)$row[$label],
                'is_correct'  => ($row['correct'] === $label),
                'opt_order'   => $order++,
            ]);
        }
    }

    /** Toma 3 capitales distintas a la correcta. */
    private function pickThreeDistinct(array $pool, string $exclude): array
    {
        $candidates = array_values(array_filter($pool, fn($x) => $x !== $exclude));
        shuffle($candidates);
        return array_slice($candidates, 0, 3);
    }

    /** Genera 3 distractores numéricos cercanos, únicos y positivos. */
    private function nearbyDistractors(int $correct): array
    {
        $alts = [];
        $deltas = [-6, -4, -3, -2, 2, 3, 4, 6, 8, 10];
        shuffle($deltas);
        foreach ($deltas as $d) {
            $v = $correct + $d;
            if ($v > 0 && $v !== $correct && !in_array($v, $alts, true)) {
                $alts[] = $v;
            }
            if (count($alts) === 3) break;
        }
        // Fallback por si acaso
        while (count($alts) < 3) {
            $alts[] = max(1, $correct + count($alts) + 2);
        }
        return $alts;
    }
}
