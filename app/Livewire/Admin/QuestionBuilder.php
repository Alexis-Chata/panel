<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\QuestionForm;
use App\Livewire\Forms\QuestionPoolForm;
use App\Models\Question;
use App\Models\QuestionPool;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.adminlte-livewire')]
#[Title('Banco de Preguntas')]
class QuestionBuilder extends Component
{
    use WithPagination;

    public QuestionPoolForm $poolForm;
    public QuestionForm $questionForm;

    public ?int $selectedPoolId = null;
    public ?int $editingQuestionId = null;

    public string $q = '';        // búsqueda de preguntas por stem/código
    public int $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    // ====== POOLS ======
    public function createPool(): void
    {
        $this->poolForm->resetToCreate();
    }

    public function editPool(int $poolId): void
    {
        $pool = QuestionPool::findOrFail($poolId);
        $this->poolForm->setFrom($pool);
    }

    public function savePool(): void
    {
        $pool = $this->poolForm->upsert();

        if (!$this->selectedPoolId) {
            $this->selectedPoolId = $pool->id;
        }
        session()->flash('ok', 'Pool guardado.');
    }

    public function deletePool(int $poolId): void
    {
        $pool = QuestionPool::findOrFail($poolId);
        $pool->questions()->delete(); // cascada simple (ajusta si usas FKs onDelete)
        $pool->delete();

        if ($this->selectedPoolId === $poolId) {
            $this->selectedPoolId = null;
            $this->questionForm->resetToCreate($poolId); // limpia
        }
        session()->flash('ok', 'Pool eliminado.');
    }

    public function selectPool(int $poolId): void
    {
        $this->selectedPoolId = $poolId;
        $this->editingQuestionId = null;
        $this->questionForm->resetToCreate($poolId);
        $this->resetPage();
    }

    // ====== QUESTIONS ======
    public function createQuestion(): void
    {
        if (!$this->selectedPoolId) return;
        $this->editingQuestionId = null;
        $this->questionForm->resetToCreate($this->selectedPoolId);
    }

    public function editQuestion(int $id): void
    {
        $q = Question::findOrFail($id);
        $this->editingQuestionId = $id;
        $this->questionForm->setFrom($q);
    }

    public function saveQuestion(): void
    {
        $q = $this->questionForm->upsert();
        $this->editingQuestionId = $q->id;
        session()->flash('ok', 'Pregunta guardada.');
    }

    public function deleteQuestion(int $id): void
    {
        $q = Question::findOrFail($id);
        $q->delete();
        $this->editingQuestionId = null;
        $this->questionForm->resetToCreate($this->selectedPoolId ?? 0);
        session()->flash('ok', 'Pregunta eliminada.');
    }

    // Opciones (delegar a form)
    public function addOptionRow(): void
    {
        $this->questionForm->addOptionRow();
    }
    public function removeOptionRow(int $i): void
    {
        $this->questionForm->removeOptionRow($i);
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function render()
    {
        $pools = QuestionPool::orderBy('name')->get(['id', 'name', 'slug', 'intended_phase']);

        $questions = Question::query()
            ->when($this->selectedPoolId, fn($q) => $q->where('question_pool_id', $this->selectedPoolId))
            ->when($this->q !== '', function ($q) {
                $t = trim($this->q);
                $q->where(function ($qq) use ($t) {
                    $qq->where('stem', 'like', '%' . $t . '%')
                        ->orWhere('code', 'like', $t . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.question-builder', compact('pools', 'questions'));
    }
}
