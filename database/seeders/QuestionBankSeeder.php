<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionPool;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pools para cada fase
        $p1 = QuestionPool::firstOrCreate(
            ['slug' => 'evaluacion-inicial'],
            ['name' => 'Evaluación inicial', 'intended_phase' => 'phase1', 'meta' => null]
        );

        $p2 = QuestionPool::firstOrCreate(
            ['slug' => 'versus-1v1'],
            ['name' => 'Versus 1v1', 'intended_phase' => 'phase2', 'meta' => null]
        );

        $p3 = QuestionPool::firstOrCreate(
            ['slug' => 'preguntas-rapidas'],
            ['name' => 'Preguntas rápidas', 'intended_phase' => 'phase3', 'meta' => null]
        );

        // ===== FASE 1: mínimo 10 preguntas
        $this->createChoiceSeries($p1, 'Capital de país', [
            ['Perú',    ['Lima', 'Cusco', 'Arequipa', 'Trujillo'], 0],
            ['Chile',   ['Santiago', 'Valparaíso', 'Concepción', 'La Serena'], 0],
            ['Argentina', ['Córdoba', 'Rosario', 'Buenos Aires', 'Mendoza'], 2],
            ['España',  ['Sevilla', 'Madrid', 'Valencia', 'Barcelona'], 1],
            ['México',  ['Monterrey', 'Guadalajara', 'CDMX', 'Puebla'], 2],
            ['Colombia', ['Bogotá', 'Medellín', 'Cali', 'Barranquilla'], 0],
        ]);

        $this->createBooleanSeries($p1, [
            ['El número π es irracional.', true],
            ['La Amazonía pasa por Perú.', true],
            ['El cero es un número natural negativo.', false],
        ]);

        $this->createNumericSeries($p1, [
            ['¿Cuánto es 12 + 15?', '27'],
            ['¿Cuánto es 9 × 7?',   '63'],
        ]);

        $this->createTextSeries($p1, [
            ['Color del cielo despejado (minúsculas).', 'azul'],
            ['Lenguaje del framework Laravel.', 'php'],
        ]);

        // ===== FASE 2: mínimo 3 preguntas
        $this->createChoiceSeries($p2, 'Mate rápida', [
            ['¿2^3 = ?', ['6', '8', '9', '12'], 1],
            ['¿Raíz de 81?', ['7', '8', '9', '10'], 2],
            ['¿10 % de 250?', ['20', '25', '30', '35'], 1],
            ['¿Logística: FIFO significa…?', ['First In First Out', 'Fast In Fast Out', 'Free In Free Out', 'None'], 0],
        ]);

        // ===== FASE 3: mínimo 10 preguntas
        $this->createChoiceSeries($p3, 'Cultura general', [
            ['Planeta rojo', ['Mercurio', 'Venus', 'Marte', 'Júpiter'], 2],
            ['Teorema de Pitágoras aplica a triángulos…', ['isosceles', 'rectángulos', 'equiláteros', 'obtusos'], 1],
            ['Autor de “Cien años de soledad”', ['Mario Vargas Llosa', 'Gabriel García Márquez', 'J. L. Borges', 'Cortázar'], 1],
            ['HTML es un…', ['lenguaje de programación', 'lenguaje de marcado', 'framework', 'servidor'], 1],
            ['CSS afecta…', ['estilos', 'base de datos', 'backend', 'hardware'], 0],
            ['HTTP 404 es…', ['OK', 'No autorizado', 'No encontrado', 'Error servidor'], 2],
            ['S.O. de Android', ['Apple', 'Google', 'Samsung', 'Huawei'], 1],
            ['Velocidad de la luz (aprox. km/s)', ['300', '3000', '300000', '30000'], 2],
            ['Continente con Sahara', ['Asia', 'África', 'Oceanía', 'Europa'], 1],
            ['Símbolo químico del oro', ['Ag', 'Au', 'Fe', 'Pt'], 1],
            ['Lenguaje de Laravel', ['Ruby', 'Python', 'PHP', 'Go'], 2],
            ['Base de datos relacional', ['MongoDB', 'Redis', 'MySQL', 'Elastic'], 2],
        ]);
    }

    private function createChoiceSeries(QuestionPool $pool, string $prefix, array $items): void
    {
        foreach ($items as $idx => $row) {
            [$stem, $options, $correctIdx] = $row;
            $q = Question::create([
                'question_pool_id' => $pool->id,
                'code' => (string) Str::uuid(),
                'type' => 'single',
                'stem' => "{$prefix}: {$stem}",
                'media' => null,
                'difficulty' => 1,
                'meta' => null,
                'time_limit_seconds' => 20
            ]);
            foreach ($options as $i => $label) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'label' => $label,
                    'value' => null,
                    'is_correct' => $i === $correctIdx,
                    'order' => $i + 1,
                ]);
            }
        }
    }

    private function createBooleanSeries(QuestionPool $pool, array $items): void
    {
        foreach ($items as [$stem, $correctTrue]) {
            $q = Question::create([
                'question_pool_id' => $pool->id,
                'code' => (string) Str::uuid(),
                'type' => 'boolean',
                'stem' => $stem,
                'media' => null,
                'difficulty' => 1,
                'meta' => null,
                'time_limit_seconds' => 20
            ]);
            // true / false
            QuestionOption::create([
                'question_id' => $q->id,
                'label' => 'Verdadero',
                'value' => 'true',
                'is_correct' => $correctTrue,
                'order' => 1,
            ]);
            QuestionOption::create([
                'question_id' => $q->id,
                'label' => 'Falso',
                'value' => 'false',
                'is_correct' => !$correctTrue,
                'order' => 2,
            ]);
        }
    }

    private function createNumericSeries(QuestionPool $pool, array $items): void
    {
        foreach ($items as [$stem, $answer]) {
            Question::create([
                'question_pool_id' => $pool->id,
                'code' => (string) Str::uuid(),
                'type' => 'numeric',
                'stem' => $stem,
                'media' => null,
                'difficulty' => 1,
                'meta' => ['answer_numeric' => (string) $answer],
                'time_limit_seconds' => 20
            ]);
        }
    }

    private function createTextSeries(QuestionPool $pool, array $items): void
    {
        foreach ($items as [$stem, $answer]) {
            Question::create([
                'question_pool_id' => $pool->id,
                'code' => (string) Str::uuid(),
                'type' => 'text',
                'stem' => $stem,
                'media' => null,
                'difficulty' => 1,
                'meta' => ['answer_text' => (string) $answer],
                'time_limit_seconds' => 20
            ]);
        }
    }
}
