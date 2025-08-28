<?php

namespace App\Livewire\Forms;

use App\Models\QuestionPool;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class QuestionPoolForm extends Form
{
    public ?int $id = null;
    public string $name = '';
    public string $slug = '';
    public ?int $intended_phase = null; // 1,2,3
    public array $meta = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('question_pools', 'slug')->ignore($this->id)],
            'intended_phase' => ['nullable', 'integer', 'in:1,2,3'],
            'meta' => ['array'],
        ];
    }

    public function setFrom(QuestionPool $pool): void
    {
        $this->id = $pool->id;
        $this->name = $pool->name;
        $this->slug = $pool->slug;
        $this->intended_phase = $pool->intended_phase;
        $this->meta = $pool->meta ?? [];
    }

    public function resetToCreate(): void
    {
        $this->id = null;
        $this->name = '';
        $this->slug = '';
        $this->intended_phase = null;
        $this->meta = [];
    }

    public function upsert(): QuestionPool
    {
        if (!$this->slug) {
            $this->slug = Str::slug($this->name);
        }
        $this->validate();

        return QuestionPool::updateOrCreate(
            ['id' => $this->id],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'intended_phase' => $this->intended_phase,
                'meta' => $this->meta,
            ]
        );
    }
}
