<?php

namespace App\Livewire;

use App\Imports\QuestionsImport;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.pregunta_index', [
    'title' => 'Banco de preguntas',
    'header' => 'Banco de preguntas',
])]
class QuestionBank extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** Estado interno de pestañas (sin ?tab= en la URL). */
    public string $activeTab = 'questions';

    public $file;

    public string $questionSearch = '';

    public ?int $filter_group_id = null;

    /** all | institutional | mine */
    public string $questionScope = 'all';

    public int $perPage = 10;

    public ?int $question_group_id = null;

    public ?int $q_id = null;

    public string $statement = '';

    public ?string $feedback = null;

    public string $qtype = 'multiple';

    public array $opts = [];

    public bool $showQuestionModal = false;

    public string $categorySearch = '';

    public ?int $cat_id = null;

    public string $cat_name = '';

    public ?string $cat_description = null;

    public bool $cat_is_active = true;

    public ?int $cat_parent_id = null;

    public bool $cat_institutional = true;

    public bool $showCategoryModal = false;

    public function mount(): void
    {
        $this->resetOptions();
        if (! in_array($this->activeTab, ['questions', 'categories'], true)) {
            $this->activeTab = 'questions';
        }
        $this->cat_institutional = Auth::user()?->hasRole('Admin') ?? false;
    }

    public function updated($prop): void
    {
        if (in_array($prop, ['questionSearch', 'perPage', 'filter_group_id', 'questionScope'], true)) {
            $this->resetPage();
        }
        if ($prop === 'categorySearch') {
            // Lista sin paginar: no hay reset de página
        }
    }

    public function updatingActiveTab(string $value): void
    {
        if ($value === 'questions') {
            $this->resetPage();
        }
    }

    protected function rules(): array
    {
        return [
            'file' => ['nullable', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ];
    }

    protected function rulesCategory(): array
    {
        return [
            'cat_name' => ['required', 'string', 'max:255'],
            'cat_description' => ['nullable', 'string'],
            'cat_is_active' => ['boolean'],
            'cat_parent_id' => ['nullable', 'exists:question_groups,id'],
            'cat_institutional' => ['boolean'],
        ];
    }

    protected function rulesForSave(): array
    {
        $rules = [
            'question_group_id' => ['required', 'exists:question_groups,id'],
            'statement' => ['required', 'string'],
            'feedback' => ['nullable', 'string'],
            'qtype' => ['required', 'in:multiple,short'],
        ];

        if ($this->qtype === 'multiple') {
            $rules = array_merge($rules, [
                'opts' => ['required', 'array', 'size:4'],
                'opts.*.label' => ['required', 'string', 'in:A,B,C,D'],
                'opts.*.content' => ['required', 'string'],
                'opts.*.is_correct' => ['boolean'],
                'opts.*.opt_order' => ['required', 'integer', 'min:1', 'max:4'],
            ]);
        }

        return $rules;
    }

    public function openCategoryCreate(?int $parentId = null): void
    {
        $this->resetCategoryForm();
        $this->cat_parent_id = $parentId;
        $this->cat_institutional = Auth::user()?->hasRole('Admin') ?? false;
        $this->showCategoryModal = true;
    }

    public function openCategoryEdit(int $id): void
    {
        $g = QuestionGroup::query()->accessibleFor(Auth::user())->findOrFail($id);

        $this->cat_id = $g->id;
        $this->cat_name = $g->name;
        $this->cat_description = $g->description;
        $this->cat_is_active = (bool) $g->is_active;
        $this->cat_parent_id = $g->parent_id;
        $this->cat_institutional = $g->user_id === null;

        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate($this->rulesCategory());

        $user = Auth::user();

        if ($this->cat_parent_id) {
            $parent = QuestionGroup::query()->accessibleFor($user)->findOrFail($this->cat_parent_id);
            abort_unless($parent->canNestChildFor($user), 403);

            $targetUserId = $parent->user_id;

            if ($targetUserId === null && ! $user->hasRole('Admin')) {
                abort(403, 'No puedes crear categorías institucionales.');
            }

            if ($targetUserId !== null && ! $user->hasRole('Admin') && (int) $targetUserId !== (int) $user->id) {
                abort(403);
            }
        } else {
            if ($user->hasRole('Admin') && $this->cat_institutional) {
                $targetUserId = null;
            } else {
                $targetUserId = $user->id;
            }
        }

        $payload = [
            'name' => $this->cat_name,
            'description' => $this->cat_description,
            'is_active' => $this->cat_is_active,
            'parent_id' => $this->cat_parent_id,
            'user_id' => $targetUserId,
        ];

        if ($this->cat_id) {
            $row = QuestionGroup::query()->accessibleFor($user)->findOrFail($this->cat_id);
            if (! $user->hasRole('Admin') && $row->user_id === null) {
                abort(403, 'Solo administración puede editar categorías institucionales.');
            }

            if ($this->cat_parent_id === (int) $this->cat_id) {
                $this->addError('cat_parent_id', 'La categoría no puede ser padre de sí misma.');

                return;
            }

            $row->update($payload);
            session()->flash('ok', 'Categoría actualizada.');
        } else {
            QuestionGroup::create($payload);
            session()->flash('ok', 'Categoría creada.');
        }

        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function confirmCategoryDelete(int $id): void
    {
        $this->cat_id = $id;
        $this->dispatch('qb-confirm-delete-category');
    }

    #[On('qb-delete-category-now')]
    public function deleteCategoryNow(): void
    {
        if (! $this->cat_id) {
            return;
        }

        $g = QuestionGroup::query()->accessibleFor(Auth::user())->findOrFail($this->cat_id);

        if (! Auth::user()->hasRole('Admin') && $g->user_id === null) {
            abort(403);
        }

        if ($g->children()->exists()) {
            session()->flash('error', 'No se puede eliminar: tiene subcategorías.');
            $this->cat_id = null;

            return;
        }

        if ($g->questions()->exists()) {
            session()->flash('error', 'No se puede eliminar: contiene preguntas.');
            $this->cat_id = null;

            return;
        }

        $g->delete();
        $this->cat_id = null;
        session()->flash('ok', 'Categoría eliminada.');
    }

    protected function resetCategoryForm(): void
    {
        $this->reset(['cat_id', 'cat_name', 'cat_description', 'cat_parent_id']);
        $this->cat_is_active = true;
        $this->cat_institutional = Auth::user()?->hasRole('Admin') ?? false;
        $this->resetValidation(['cat_name', 'cat_description', 'cat_parent_id']);
    }

    public function openQuestionCreate(): void
    {
        $this->resetQuestionForm();
        $gid = $this->filter_group_id;
        if ($gid && QuestionGroup::query()->accessibleFor(Auth::user())->whereKey($gid)->exists()) {
            $this->question_group_id = $gid;
        }
        $this->showQuestionModal = true;
    }

    public function openQuestionEdit(int $id): void
    {
        $q = Question::query()
            ->where(function ($qq) {
                $qq->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->findOrFail($id);

        $group = QuestionGroup::findOrFail($q->question_group_id);
        abort_unless($group->isAccessibleBy(Auth::user()), 403);

        $this->q_id = $q->id;
        $this->statement = $q->statement;
        $this->feedback = $q->feedback;
        $this->qtype = $q->qtype ?? 'multiple';
        $this->question_group_id = $q->question_group_id;

        if ($this->qtype === 'multiple') {
            $opts = $q->options->sortBy('opt_order')->values();
            if ($opts->isEmpty()) {
                $this->resetOptions();
            } else {
                $this->opts = $opts->map(fn ($o) => [
                    'label' => $o->label,
                    'content' => $o->content,
                    'is_correct' => (bool) $o->is_correct,
                    'opt_order' => (int) $o->opt_order,
                ])->toArray();
            }
        } else {
            $this->resetOptions();
        }

        $this->showQuestionModal = true;
    }

    public function setCorrectOption(int $index): void
    {
        foreach ($this->opts as $i => &$opt) {
            $opt['is_correct'] = ($i === $index);
        }
        unset($opt);
    }

    public function saveQuestion(): void
    {
        $this->validate($this->rulesForSave());

        $group = QuestionGroup::query()->accessibleFor(Auth::user())->findOrFail($this->question_group_id);

        if ($this->qtype === 'multiple') {
            $corrects = array_values(array_filter($this->opts, fn ($o) => ! empty($o['is_correct'])));
            if (count($corrects) !== 1) {
                $this->addError('opts', 'Debes marcar exactamente 1 alternativa correcta.');

                return;
            }
        }

        $ownerId = $group->user_id;

        if ($this->q_id) {
            $q = Question::query()
                ->where(function ($qq) {
                    $qq->whereNull('user_id')->orWhere('user_id', Auth::id());
                })
                ->findOrFail($this->q_id);

            abort_unless($q->group->isAccessibleBy(Auth::user()), 403);
            if (! Auth::user()->hasRole('Admin') && $q->user_id === null) {
                abort(403, 'Solo administración puede editar preguntas institucionales.');
            }

            $q->update([
                'statement' => $this->statement,
                'feedback' => $this->feedback,
                'qtype' => $this->qtype,
                'question_group_id' => $this->question_group_id,
                'user_id' => $ownerId,
            ]);

            $q->options()->delete();
        } else {
            $q = Question::create([
                'statement' => $this->statement,
                'feedback' => $this->feedback,
                'qtype' => $this->qtype,
                'question_group_id' => $this->question_group_id,
                'user_id' => $ownerId,
            ]);
        }

        if ($this->qtype === 'multiple') {
            foreach ($this->opts as $o) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'label' => $o['label'],
                    'content' => $o['content'],
                    'is_correct' => ! empty($o['is_correct']),
                    'opt_order' => (int) $o['opt_order'],
                ]);
            }
        }

        $this->resetQuestionForm();
        $this->showQuestionModal = false;
        session()->flash('ok', 'Pregunta guardada correctamente.');
    }

    public function confirmQuestionDelete(int $id): void
    {
        $this->q_id = $id;
        $this->dispatch('qb-confirm-delete-question');
    }

    #[On('qb-delete-question-now')]
    public function deleteQuestionNow(): void
    {
        if (! $this->q_id) {
            return;
        }

        $q = Question::query()
            ->where(function ($qq) {
                $qq->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->findOrFail($this->q_id);

        if (! Auth::user()->hasRole('Admin') && $q->user_id === null) {
            abort(403, 'Solo administración puede eliminar preguntas institucionales.');
        }

        $q->delete();
        $this->q_id = null;
        session()->flash('ok', 'Pregunta eliminada.');
    }

    public function import(): void
    {
        $this->validateOnly('file');

        if (! $this->file) {
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

    protected function resetOptions(): void
    {
        $this->opts = [
            ['label' => 'A', 'content' => '', 'is_correct' => false, 'opt_order' => 1],
            ['label' => 'B', 'content' => '', 'is_correct' => false, 'opt_order' => 2],
            ['label' => 'C', 'content' => '', 'is_correct' => false, 'opt_order' => 3],
            ['label' => 'D', 'content' => '', 'is_correct' => false, 'opt_order' => 4],
        ];
    }

    protected function resetQuestionForm(): void
    {
        $this->reset(['q_id', 'statement', 'feedback', 'question_group_id']);
        $this->qtype = 'multiple';
        $this->resetOptions();
        $this->resetValidation();
    }

    /** Jerarquía para selects (id => etiqueta indentada). */
    public function flatCategoryTree(): array
    {
        $roots = QuestionGroup::query()
            ->accessibleFor(Auth::user())
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $out = [];
        foreach ($roots as $r) {
            $this->walkCategoryBranch($r, 0, $out);
        }

        return $out;
    }

    protected function walkCategoryBranch(QuestionGroup $node, int $depth, array &$out): void
    {
        $prefix = str_repeat('— ', $depth);
        $suffix = $node->user_id ? ' · personal' : '';
        $out[$node->id] = $prefix.$node->name.$suffix;

        $children = QuestionGroup::query()
            ->accessibleFor(Auth::user())
            ->where('parent_id', $node->id)
            ->orderBy('name')
            ->get();

        foreach ($children as $ch) {
            $this->walkCategoryBranch($ch, $depth + 1, $out);
        }
    }

    public function render(): View
    {
        $flatGroups = $this->flatCategoryTree();

        $categoryRows = QuestionGroup::query()
            ->accessibleFor(Auth::user())
            ->with('parent:id,name')
            ->withCount('questions')
            ->when($this->categorySearch, function ($q) {
                $s = '%'.$this->categorySearch.'%';
                $q->where(fn ($qq) => $qq->where('name', 'like', $s)->orWhere('description', 'like', $s));
            })
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->limit(400)
            ->get();

        $qQuery = Question::query()
            ->with(['group:id,name,parent_id,user_id'])
            ->when($this->filter_group_id, fn ($qq) => $qq->where('question_group_id', $this->filter_group_id))
            ->when($this->questionSearch, function ($qq) {
                $s = '%'.$this->questionSearch.'%';
                $qq->where(fn ($sub) => $sub->where('statement', 'like', $s)->orWhere('feedback', 'like', $s));
            })
            ->when($this->questionScope === 'institutional', fn ($qq) => $qq->whereNull('user_id'))
            ->when($this->questionScope === 'mine', fn ($qq) => $qq->where('user_id', Auth::id()));

        $qQuery->whereHas('group', fn ($gq) => $gq->accessibleFor(Auth::user()))
            ->where(fn ($qq) => $qq->whereNull('user_id')->orWhere('user_id', Auth::id()));

        $totalAccessibleQuestions = (clone $qQuery)->count();

        $questionRows = $qQuery->latest()->paginate($this->perPage);

        return view('livewire.question-bank', [
            'categoryRows' => $categoryRows,
            'flatGroups' => $flatGroups,
            'questionRows' => $questionRows,
            'totalAccessibleQuestions' => $totalAccessibleQuestions,
        ]);
    }
}
