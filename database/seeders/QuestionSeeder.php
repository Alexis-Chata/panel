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
        $base = [
            [
                'statement' => '¿Capital de Perú?',
                'feedback'  => 'Lima es la capital y ciudad más poblada del Perú.',
                'correct'   => 'A',
                'A' => 'Lima',
                'B' => 'Cusco',
                'C' => 'Arequipa',
                'D' => 'Trujillo',
            ],
            [
                'statement' => '¿Resultado de 7 × 8?',
                'feedback'  => '7 × 8 = 56.',
                'correct'   => 'B',
                'A' => '54',
                'B' => '56',
                'C' => '58',
                'D' => '60',
            ],
            [
                'statement' => 'El agua hierve a nivel del mar a…',
                'feedback'  => 'A 1 atm de presión, el agua hierve a 100 °C.',
                'correct'   => 'C',
                'A' => '80 °C',
                'B' => '90 °C',
                'C' => '100 °C',
                'D' => '120 °C',
            ],
            [
                'statement' => '¿Cuál es el río más largo del mundo?',
                'feedback'  => 'Amazonas y Nilo compiten, pero hoy se acepta mayoritariamente el Amazonas.',
                'correct'   => 'A',
                'A' => 'Amazonas',
                'B' => 'Nilo',
                'C' => 'Yangtsé',
                'D' => 'Misisipi',
            ],
            [
                'statement' => '¿Quién formuló la teoría de la relatividad?',
                'feedback'  => 'Albert Einstein publicó la relatividad especial (1905) y general (1915).',
                'correct'   => 'B',
                'A' => 'Isaac Newton',
                'B' => 'Albert Einstein',
                'C' => 'Niels Bohr',
                'D' => 'Galileo Galilei',
            ],
            [
                'statement' => '¿Cuál es el océano más grande?',
                'feedback'  => 'El océano Pacífico es el más grande del planeta.',
                'correct'   => 'A',
                'A' => 'Pacífico',
                'B' => 'Atlántico',
                'C' => 'Índico',
                'D' => 'Ártico',
            ],
            [
                'statement' => '¿Cuál es el planeta más cercano al Sol?',
                'feedback'  => 'Mercurio es el más cercano.',
                'correct'   => 'D',
                'A' => 'Venus',
                'B' => 'Tierra',
                'C' => 'Marte',
                'D' => 'Mercurio',
            ],
            [
                'statement' => '¿En qué continente está Egipto?',
                'feedback'  => 'Egipto se sitúa en África (con una pequeña porción en Asia: la península del Sinaí).',
                'correct'   => 'C',
                'A' => 'Europa',
                'B' => 'Oceanía',
                'C' => 'África',
                'D' => 'América',
            ],
            [
                'statement' => '¿Quién pintó “La Gioconda”?',
                'feedback'  => 'Leonardo da Vinci.',
                'correct'   => 'A',
                'A' => 'Leonardo da Vinci',
                'B' => 'Miguel Ángel',
                'C' => 'Rafael',
                'D' => 'Botticelli',
            ],
            [
                'statement' => '¿Cuál es el metal cuyo símbolo químico es Fe?',
                'feedback'  => 'Fe corresponde a Hierro (Ferrum en latín).',
                'correct'   => 'B',
                'A' => 'Cobre',
                'B' => 'Hierro',
                'C' => 'Plata',
                'D' => 'Oro',
            ],
            [
                'statement' => '¿En qué país se encuentra la Torre Eiffel?',
                'feedback'  => 'En Francia, ciudad de París.',
                'correct'   => 'A',
                'A' => 'Francia',
                'B' => 'Italia',
                'C' => 'España',
                'D' => 'Alemania',
            ],
            [
                'statement' => '¿Qué gas respiramos principalmente?',
                'feedback'  => 'El aire está compuesto mayormente por Nitrógeno (~78%).',
                'correct'   => 'D',
                'A' => 'Oxígeno',
                'B' => 'Dióxido de carbono',
                'C' => 'Argón',
                'D' => 'Nitrógeno',
            ],
            [
                'statement' => '¿Cuál es el idioma más hablado como lengua materna?',
                'feedback'  => 'El chino mandarín tiene más hablantes nativos.',
                'correct'   => 'C',
                'A' => 'Inglés',
                'B' => 'Español',
                'C' => 'Chino mandarín',
                'D' => 'Hindi',
            ],
            [
                'statement' => '¿Qué número es primo?',
                'feedback'  => 'El 13 es primo.',
                'correct'   => 'B',
                'A' => '21',
                'B' => '13',
                'C' => '15',
                'D' => '27',
            ],
            [
                'statement' => '¿Cuál es la capa más externa de la Tierra?',
                'feedback'  => 'La corteza terrestre es la capa más externa.',
                'correct'   => 'A',
                'A' => 'Corteza',
                'B' => 'Manto',
                'C' => 'Núcleo externo',
                'D' => 'Núcleo interno',
            ],
        ];

        foreach ($base as $row) {
            $this->storeQuestion($row);
        }

        // =========
        // 2) CAPITALES (15 preguntas generadas)
        // =========
        $capitals = [
            ['Argentina', 'Buenos Aires'],
            ['Chile', 'Santiago'],
            ['Uruguay', 'Montevideo'],
            ['Brasil', 'Brasilia'],
            ['Bolivia', 'Sucre'],
            ['Paraguay', 'Asunción'],
            ['Colombia', 'Bogotá'],
            ['Ecuador', 'Quito'],
            ['Venezuela', 'Caracas'],
            ['México', 'Ciudad de México'],
            ['España', 'Madrid'],
            ['Italia', 'Roma'],
            ['Francia', 'París'],
            ['Alemania', 'Berlín'],
            ['Japón', 'Tokio'],
        ];
        $allCaps = array_map(fn($x) => $x[1], $capitals);

        foreach ($capitals as [$country, $cap]) {
            $fakeDistractors = $this->pickThreeDistinct($allCaps, $cap);
            $row = [
                'statement' => "¿Capital de {$country}?",
                'feedback'  => "{$cap} es la capital de {$country}.",
                'correct'   => 'A',
                'A' => $cap,
                'B' => $fakeDistractors[0],
                'C' => $fakeDistractors[1],
                'D' => $fakeDistractors[2],
            ];
            $this->storeQuestion($row);
        }

        // =========
        // 3) ARITMÉTICA (20 preguntas  — multiplicaciones variadas)
        // =========
        $pairs = [
            [3, 6],
            [3, 7],
            [3, 8],
            [4, 6],
            [4, 7],
            [4, 8],
            [5, 6],
            [5, 7],
            [5, 8],
            [6, 6],
            [6, 7],
            [6, 8],
            [7, 7],
            [7, 8],
            [8, 8],
            [9, 6],
            [9, 7],
            [9, 8],
            [11, 7],
            [12, 8],
        ];
        foreach ($pairs as [$a, $b]) {
            $correct = $a * $b;
            $d = $this->nearbyDistractors($correct);
            $row = [
                'statement' => "¿Cuánto es {$a} × {$b}?",
                'feedback'  => "{$a} × {$b} = {$correct}.",
                'correct'   => 'A',
                'A' => (string)$correct,
                'B' => (string)$d[0],
                'C' => (string)$d[1],
                'D' => (string)$d[2],
            ];
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
