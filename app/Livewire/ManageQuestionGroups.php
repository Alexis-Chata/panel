<?php

namespace App\Livewire;

use App\Models\QuestionGroup;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.pregunta_index', [
    'title'  => 'Grupos de Preguntas',
    'header' => 'Grupos de Preguntas',
])]
class ManageQuestionGroups extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public ?int $group_id = null;
    public string $name = '';
    public ?string $description = null;
    public bool $is_active = true;

    public bool $showModal = false;

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ];
    }

    public function updated($prop): void
    {
        if (in_array($prop, ['search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $g = QuestionGroup::findOrFail($id);

        $this->group_id    = $g->id;
        $this->name        = $g->name;
        $this->description = $g->description;
        $this->is_active   = (bool) $g->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->group_id) {
            QuestionGroup::whereKey($this->group_id)->update([
                'name'        => $this->name,
                'description' => $this->description,
                'is_active'   => $this->is_active,
            ]);
        } else {
            QuestionGroup::create([
                'name'        => $this->name,
                'description' => $this->description,
                'is_active'   => $this->is_active,
            ]);
        }

        $this->resetForm();
        $this->showModal = false;
        session()->flash('ok', 'Grupo guardado correctamente.');
    }

    public function confirmDelete(int $id): void
    {
        $this->group_id = $id;
        $this->dispatch('confirm-delete-group');
    }

    #[\Livewire\Attributes\On('delete-group-now')]
    public function deleteNow(): void
    {
        if ($this->group_id) {
            QuestionGroup::whereKey($this->group_id)->delete();
            $this->group_id = null;
            session()->flash('ok', 'Grupo eliminado.');
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['group_id', 'name', 'description', 'is_active']);
        $this->is_active = true;
    }

    public function render()
    {
        $groups = QuestionGroup::query()
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->where('name', 'like', $s)
                    ->orWhere('description', 'like', $s);
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.manage-question-groups', [
            'rows' => $groups,
        ]);
    }
}
