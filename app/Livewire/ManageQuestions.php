<?php

namespace App\Livewire;

use App\Models\Question;
use App\Models\QuestionOption;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.adminlte', ['title' => 'Banco de Preguntas', 'header' => 'Banco de Preguntas'])]
class ManageQuestions extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $file; // input de archivo

    public string $search = '';
    public int $perPage = 10;

    // Formulario
    public ?int $q_id = null;
    public string $statement = '';
    public ?string $feedback = null;
    public array $opts = [
        ['label' => 'A', 'content' => '', 'is_correct' => false, 'opt_order' => 1],
        ['label' => 'B', 'content' => '', 'is_correct' => false, 'opt_order' => 2],
        ['label' => 'C', 'content' => '', 'is_correct' => false, 'opt_order' => 3],
        ['label' => 'D', 'content' => '', 'is_correct' => false, 'opt_order' => 4],
    ];

    public bool $showModal = false;

    protected function rules(): array
    {
        return [
            'statement' => ['required', 'string'],
            'feedback'  => ['nullable', 'string'],
            'opts'      => ['required', 'array', 'size:4'],
            'opts.*.label' => ['required', 'string', 'in:A,B,C,D'],
            'opts.*.content' => ['required', 'string'],
            'opts.*.is_correct' => ['boolean'],
            'opts.*.opt_order' => ['required', 'integer', 'min:1', 'max:4'],

            // import
            'file' => ['nullable', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ];
    }

    public function updated($prop): void
    {
        if (str_starts_with($prop, 'search')) {
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
        $q = Question::with('options')->findOrFail($id);
        $this->q_id = $q->id;
        $this->statement = $q->statement;
        $this->feedback = $q->feedback;

        $opts = $q->options->sortBy('opt_order')->values();
        $this->opts = $opts->map(function ($o) {
            return [
                'label' => $o->label,
                'content' => $o->content,
                'is_correct' => (bool)$o->is_correct,
                'opt_order' => (int)$o->opt_order,
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        // Debe haber exactamente 1 correcta (puedes cambiar a >=1 si deseas múltiples)
        $corrects = array_values(array_filter($this->opts, fn($o) => !empty($o['is_correct'])));
        if (count($corrects) !== 1) {
            $this->addError('opts', 'Debes marcar exactamente 1 alternativa correcta.');
            return;
        }

        if ($this->q_id) {
            $q = Question::findOrFail($this->q_id);
            $q->update([
                'statement' => $this->statement,
                'feedback' => $this->feedback,
            ]);
            // Reemplazo simple de opciones
            $q->options()->delete();
        } else {
            $q = Question::create([
                'statement' => $this->statement,
                'feedback' => $this->feedback,
            ]);
        }

        foreach ($this->opts as $o) {
            QuestionOption::create([
                'question_id' => $q->id,
                'label'       => $o['label'],
                'content'     => $o['content'],
                'is_correct'  => !empty($o['is_correct']),
                'opt_order'   => (int)$o['opt_order'],
            ]);
        }

        $this->resetForm();
        $this->showModal = false;
        session()->flash('ok', 'Pregunta guardada correctamente');
    }

    public function confirmDelete(int $id): void
    {
        $this->q_id = $id;
        $this->dispatch('confirm-delete'); // front: modal nativo/JS
    }

    #[\Livewire\Attributes\On('delete-now')]
    public function deleteNow(): void
    {
        if ($this->q_id) {
            Question::whereKey($this->q_id)->delete();
            $this->q_id = null;
            session()->flash('ok', 'Eliminado.');
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['q_id', 'statement', 'feedback']);
        $this->opts = [
            ['label' => 'A', 'content' => '', 'is_correct' => false, 'opt_order' => 1],
            ['label' => 'B', 'content' => '', 'is_correct' => false, 'opt_order' => 2],
            ['label' => 'C', 'content' => '', 'is_correct' => false, 'opt_order' => 3],
            ['label' => 'D', 'content' => '', 'is_correct' => false, 'opt_order' => 4],
        ];
    }

    public function import()
    {
        $this->validateOnly('file');

        if (!$this->file) {
            $this->addError('file', 'Selecciona un archivo.');
            return;
        }

        try {
            Excel::import(new QuestionsImport, $this->file->getRealPath());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('file', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->addError('file', 'Archivo inválido o con columnas incorrectas.');
            report($e);
            return;
        }

        $this->reset('file');
        session()->flash('ok', 'Preguntas importadas correctamente.');
    }

    public function render()
    {
        $q = Question::query()
            ->when(
                $this->search,
                fn($qq) =>
                $qq->where('statement', 'like', '%' . $this->search . '%')
                    ->orWhere('feedback', 'like', '%' . $this->search . '%')
            )
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.manage-questions', ['rows' => $q]);
    }
}
