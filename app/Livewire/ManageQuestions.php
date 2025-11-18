<?php

namespace App\Livewire;

use App\Imports\QuestionsImport;
use App\Models\Question;
use App\Models\QuestionOption;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.pregunta_index', ['title' => 'Banco de Preguntas', 'header' => 'Banco de Preguntas'])]
class ManageQuestions extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $file; // input de archivo

    public string $search = '';
    public int $perPage = 10;

    // Tipo de pregunta: 'multiple' o 'short'
    public string $qtype = 'multiple';

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

    /**
     * Reglas generales (las usamos sobre todo para el import con validateOnly('file'))
     */
    protected function rules(): array
    {
        return [
            // Import
            'file' => ['nullable', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ];
    }

    public function updated($prop): void
    {
        // Si cambia búsqueda o cantidad por página, reinicia la página
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
        $q = Question::with('options')->findOrFail($id);

        $this->q_id      = $q->id;
        $this->statement = $q->statement;
        $this->feedback  = $q->feedback;
        $this->qtype     = $q->qtype ?? 'multiple';

        // Si es multiple, cargamos las opciones; si es short, mantenemos las por defecto (no se guardan)
        if ($this->qtype === 'multiple') {
            $opts = $q->options->sortBy('opt_order')->values();

            if ($opts->isEmpty()) {
                $this->resetOptions();
            } else {
                $this->opts = $opts->map(function ($o) {
                    return [
                        'label'      => $o->label,
                        'content'    => $o->content,
                        'is_correct' => (bool) $o->is_correct,
                        'opt_order'  => (int) $o->opt_order,
                    ];
                })->toArray();
            }
        } else {
            // short: las opciones no importan (no se usarán), pero dejamos el array por si acaso
            $this->resetOptions();
        }

        $this->showModal = true;
    }

    /**
     * Seleccionar una única alternativa correcta.
     */
    public function setCorrectOption(int $index): void
    {
        foreach ($this->opts as $i => &$opt) {
            $opt['is_correct'] = ($i === $index);
        }
        unset($opt);
    }

    /**
     * Reglas específicas para guardar pregunta (dinámicas según qtype).
     */
    protected function rulesForSave(): array
    {
        $rules = [
            'statement' => ['required', 'string'],
            'feedback'  => ['nullable', 'string'],
            'qtype'     => ['required', 'in:multiple,short'],
        ];

        if ($this->qtype === 'multiple') {
            $rules = array_merge($rules, [
                'opts'                => ['required', 'array', 'size:4'],
                'opts.*.label'        => ['required', 'string', 'in:A,B,C,D'],
                'opts.*.content'      => ['required', 'string'],
                'opts.*.is_correct'   => ['boolean'],
                'opts.*.opt_order'    => ['required', 'integer', 'min:1', 'max:4'],
            ]);
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate($this->rulesForSave());

        // Si es multiple: debe haber exactamente 1 correcta
        if ($this->qtype === 'multiple') {
            $corrects = array_values(array_filter($this->opts, fn($o) => !empty($o['is_correct'])));
            if (count($corrects) !== 1) {
                $this->addError('opts', 'Debes marcar exactamente 1 alternativa correcta.');
                return;
            }
        }

        if ($this->q_id) {
            $q = Question::findOrFail($this->q_id);
            $q->update([
                'statement' => $this->statement,
                'feedback'  => $this->feedback,
                'qtype'     => $this->qtype,
            ]);

            // Si es multiple, reemplazamos opciones; si es short, borramos cualquier opción antigua
            $q->options()->delete();
        } else {
            $q = Question::create([
                'statement' => $this->statement,
                'feedback'  => $this->feedback,
                'qtype'     => $this->qtype,
            ]);
        }

        // Solo creamos opciones cuando es de opción múltiple
        if ($this->qtype === 'multiple') {
            foreach ($this->opts as $o) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'label'       => $o['label'],
                    'content'     => $o['content'],
                    'is_correct'  => !empty($o['is_correct']),
                    'opt_order'   => (int) $o['opt_order'],
                ]);
            }
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

    protected function resetOptions(): void
    {
        $this->opts = [
            ['label' => 'A', 'content' => '', 'is_correct' => false, 'opt_order' => 1],
            ['label' => 'B', 'content' => '', 'is_correct' => false, 'opt_order' => 2],
            ['label' => 'C', 'content' => '', 'is_correct' => false, 'opt_order' => 3],
            ['label' => 'D', 'content' => '', 'is_correct' => false, 'opt_order' => 4],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['q_id', 'statement', 'feedback']);
        $this->qtype = 'multiple';
        $this->resetOptions();
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
            ->when($this->search, function ($qq) {
                $s = '%' . $this->search . '%';
                $qq->where(function ($sub) use ($s) {
                    $sub->where('statement', 'like', $s)
                        ->orWhere('feedback', 'like', $s);
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.manage-questions', ['rows' => $q]);
    }
}
