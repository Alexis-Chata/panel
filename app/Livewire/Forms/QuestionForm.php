<?php

namespace App\Livewire\Forms;

use App\Models\Question;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class QuestionForm extends Form
{
    public ?int $id = null;
    public ?int $question_pool_id = null;

    public string $code = '';
    public string $type = 'multiple_choice'; // multiple_choice|true_false|short_answer
    public string $stem = '';
    public array $media = [];               // ['image' => '...', 'audio' => '...']
    public ?int $difficulty = null;         // 1..5 (opcional)
    public array $meta = [];                // libre
    public ?int $time_limit_seconds = null; // opcional

    // Options para multiple_choice/true_false
    public array $options = []; // [['label'=>'A','value'=>'Texto','is_correct'=>false,'order'=>1], ...]

    protected function rules(): array
    {
        return [
            'question_pool_id' => ['required', 'integer', 'exists:question_pools,id'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('questions', 'code')->ignore($this->id)],
            'type' => ['required', 'string', 'in:multiple_choice,true_false,short_answer'],
            'stem' => ['required', 'string', 'max:2000'],
            'media' => ['array'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'meta' => ['array'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],

            'options' => ['array'],
            'options.*.label' => ['required_if:type,multiple_choice,true_false', 'string', 'max:10'],
            'options.*.value' => ['required_if:type,multiple_choice,true_false', 'string', 'max:1000'],
            'options.*.is_correct' => ['boolean'],
            'options.*.order' => ['integer', 'min:0', 'max:999'],
        ];
    }

    public function setFrom(Question $q): void
    {
        $this->id = $q->id;
        $this->question_pool_id = $q->question_pool_id;
        $this->code = $q->code ?? '';
        $this->type = $q->type;
        $this->stem = $q->stem;
        $this->media = $q->media ?? [];
        $this->difficulty = $q->difficulty;
        $this->meta = $q->meta ?? [];
        $this->time_limit_seconds = $q->time_limit_seconds;

        $this->options = $q->options()
            ->orderBy('order')
            ->get(['label', 'value', 'is_correct', 'order'])
            ->map(fn($o) => [
                'label' => $o->label,
                'value' => $o->value,
                'is_correct' => (bool)$o->is_correct,
                'order' => $o->order
            ])->toArray();
    }

    public function resetToCreate(int $poolId): void
    {
        $this->id = null;
        $this->question_pool_id = $poolId;
        $this->code = '';
        $this->type = 'multiple_choice';
        $this->stem = '';
        $this->media = [];
        $this->difficulty = null;
        $this->meta = [];
        $this->time_limit_seconds = null;

        $this->options = [
            ['label' => 'A', 'value' => '', 'is_correct' => false, 'order' => 1],
            ['label' => 'B', 'value' => '', 'is_correct' => false, 'order' => 2],
            ['label' => 'C', 'value' => '', 'is_correct' => false, 'order' => 3],
            ['label' => 'D', 'value' => '', 'is_correct' => false, 'order' => 4],
        ];
    }

    public function addOptionRow(): void
    {
        $next = count($this->options) + 1;
        $label = chr(64 + $next); // A,B,C...
        $this->options[] = ['label' => $label, 'value' => '', 'is_correct' => false, 'order' => $next];
    }

    public function removeOptionRow(int $i): void
    {
        if (isset($this->options[$i])) {
            array_splice($this->options, $i, 1);
            // reordenar
            foreach ($this->options as $k => $row) {
                $this->options[$k]['order'] = $k + 1;
                $this->options[$k]['label'] = chr(64 + $this->options[$k]['order']);
            }
        }
    }

    public function upsert(): Question
    {
        // Si no hay code, genera uno sencillo
        if (!$this->code) {
            $this->code = strtoupper(Str::random(6));
        }

        $this->validate();

        // Para TF, fuerza 2 opciones
        if ($this->type === 'true_false') {
            $this->options = [
                ['label' => 'V', 'value' => 'Verdadero', 'is_correct' => false, 'order' => 1],
                ['label' => 'F', 'value' => 'Falso', 'is_correct' => false, 'order' => 2],
            ];
        }

        // Al menos 1 correcta si es multiple_choice o true_false
        if (in_array($this->type, ['multiple_choice', 'true_false'], true)) {
            $anyCorrect = collect($this->options)->contains(fn($o) => !empty($o['is_correct']));
            if (!$anyCorrect) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options' => 'Debe marcar al menos una opción correcta.',
                ]);
            }
        }

        $q = Question::updateOrCreate(
            ['id' => $this->id],
            [
                'question_pool_id' => $this->question_pool_id,
                'code' => $this->code,
                'type' => $this->type,
                'stem' => $this->stem,
                'media' => $this->media,
                'difficulty' => $this->difficulty,
                'meta' => $this->meta,
                'time_limit_seconds' => $this->time_limit_seconds,
            ]
        );

        // Sync options
        if (in_array($this->type, ['multiple_choice', 'true_false'], true)) {
            $q->options()->delete();
            foreach ($this->options as $row) {
                $q->options()->create([
                    'label' => $row['label'],
                    'value' => $row['value'],
                    'is_correct' => (bool)$row['is_correct'],
                    'order' => (int)$row['order'],
                ]);
            }
        } else {
            // short_answer: sin opciones
            $q->options()->delete();
        }

        return $q;
    }
}
