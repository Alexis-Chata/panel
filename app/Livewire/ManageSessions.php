<?php

namespace App\Livewire;

use App\Livewire\Forms\GameSessionForm;
use App\Models\Archivo;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\SessionQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageSessions extends Component
{
    use WithFileUploads;

    public GameSessionForm $form;

    public array $uploads = [];

    /** Modal de composición del cuestionario */
    public ?int $compositionSessionId = null;

    public $compositionQuestionGroupId = null;

    public string $questionPickMode = 'random';

    public array $selectedQuestionIds = [];

    public int $randomExtraCount = 0;

    public string $poolQuestionSearch = '';

    public function toggleStudentViewMode(GameSession $gameSession): void
    {
        $gameSession->student_view_mode = $gameSession->student_view_mode === 'completo'
            ? 'solo_alternativas'
            : 'completo';
        $gameSession->save();
    }

    public function getNextViewModeLabel(GameSession $gameSession): string
    {
        return $gameSession->student_view_mode === 'completo'
            ? 'Solo alternativas'
            : 'Completo';
    }

    public function removeArchivo(Archivo $archivo): void
    {
        $res = $this->form->deleteArchivo($archivo);
        $this->dispatch('toast', body: $res['message'] ?? 'Actualizado');

        $this->form->gameSession?->refresh();
    }

    public function nuevo(): void
    {
        $this->form->reset();
        $this->uploads = [];
        $this->questionPickMode = 'random';
        $this->selectedQuestionIds = [];
        $this->randomExtraCount = 0;
        $this->poolQuestionSearch = '';
        $this->compositionSessionId = null;
        $this->compositionQuestionGroupId = null;
    }

    public function editar(GameSession $gameSession): void
    {
        $this->form->set($gameSession);
        $this->uploads = [];
        $this->questionPickMode = 'random';
        $this->selectedQuestionIds = [];
        $this->randomExtraCount = 0;
        $this->poolQuestionSearch = '';
        $this->compositionSessionId = null;
        $this->compositionQuestionGroupId = null;
    }

    public function updatedQuestionPickMode(string $value): void
    {
        if ($value === 'random') {
            $this->selectedQuestionIds = [];
            $this->randomExtraCount = 0;
        }
    }

    public function updated($name): void
    {
        if ($name === 'compositionQuestionGroupId') {
            $this->selectedQuestionIds = [];
            $this->poolQuestionSearch = '';
        }
    }

    public function togglePoolQuestion(int $questionId): void
    {
        if (in_array($questionId, $this->selectedQuestionIds, true)) {
            $this->selectedQuestionIds = array_values(array_filter(
                $this->selectedQuestionIds,
                fn (int $id) => $id !== $questionId
            ));
        } else {
            $this->selectedQuestionIds[] = $questionId;
        }
    }

    public function openConfigureQuestions(int $sessionId): void
    {
        $session = GameSession::query()->findOrFail($sessionId);

        $this->compositionSessionId = $session->id;
        $this->compositionQuestionGroupId = $session->question_group_id
            ? (string) $session->question_group_id
            : '';
        $this->questionPickMode = 'random';
        $this->selectedQuestionIds = [];
        $this->randomExtraCount = 0;
        $this->poolQuestionSearch = '';
        $this->form->questions_total = 10;

        $this->js('$("#sessionQuestionsModal").modal("show");');
    }

    public function closeConfigureQuestions(): void
    {
        $this->compositionSessionId = null;
        $this->compositionQuestionGroupId = null;
        $this->questionPickMode = 'random';
        $this->selectedQuestionIds = [];
        $this->randomExtraCount = 0;
        $this->poolQuestionSearch = '';
        $this->js('$("#sessionQuestionsModal").modal("hide");');
    }

    public function save_question_composition(): void
    {
        if (! $this->compositionSessionId) {
            return;
        }

        $this->validate([
            'compositionQuestionGroupId' => ['required', 'exists:question_groups,id'],
        ]);

        $groupId = (int) $this->compositionQuestionGroupId;

        $groupOk = QuestionGroup::query()->accessibleFor(Auth::user())->whereKey($groupId)->exists();
        if (! $groupOk) {
            $this->addError('compositionQuestionGroupId', 'No tienes acceso a esa categoría.');

            return;
        }

        $orderedIds = $this->resolveCompositionQuestionIds($groupId);
        if ($orderedIds === null) {
            return;
        }

        $session = GameSession::query()->findOrFail($this->compositionSessionId);

        SessionQuestion::query()->where('game_session_id', $session->id)->delete();

        $payload = [];
        foreach ($orderedIds as $i => $qid) {
            $payload[] = [
                'game_session_id' => $session->id,
                'question_id' => $qid,
                'q_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($payload !== []) {
            SessionQuestion::insert($payload);
        }

        $session->update([
            'question_group_id' => $groupId,
            'questions_total' => count($orderedIds),
        ]);

        $this->compositionSessionId = null;
        $this->compositionQuestionGroupId = null;
        $this->selectedQuestionIds = [];
        $this->randomExtraCount = 0;
        $this->poolQuestionSearch = '';

        $this->js('$("#sessionQuestionsModal").modal("hide");');

        session()->flash('ok', 'Preguntas del cuestionario guardadas.');
    }

    /**
     * Crear partida: sólo título (paso siguiente = modal de preguntas desde la tabla).
     */
    public function save_session()
    {
        $this->validate([
            'uploads.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png,webp,doc,docx,ppt,pptx,zip',
        ]);

        $isEdit = (bool) $this->form->gameSession?->id;

        if (! $isEdit) {
            $this->form->storeBare();

            $msg = 'Partida creada. Asigna las preguntas desde la columna «Preguntas».';

            $this->uploads = [];
            $this->form->reset();
            $this->dispatch('cerrar_modal_gamesession');

            session()->flash('ok', $msg);

            return;
        }

        $this->form->update();

        if (! empty($this->uploads)) {
            $this->form->attachFiles($this->uploads);
        }

        $this->uploads = [];
        $this->form->reset();

        $this->dispatch('cerrar_modal_gamesession');

        session()->flash('ok', 'Partida actualizada correctamente.');
    }

    /**
     * @return array<int>|null
     */
    protected function resolveCompositionQuestionIds(int $groupId): ?array
    {
        $mode = $this->questionPickMode;

        if ($mode === 'random') {
            $totalAvailable = $this->basePoolQuery($groupId)->count();
            if ($totalAvailable === 0) {
                $this->addError('compositionQuestionGroupId', 'No hay preguntas disponibles en la categoría seleccionada.');

                return null;
            }
            $n = min(max(1, (int) $this->form->questions_total), $totalAvailable);
            $this->form->questions_total = $n;

            return $this->basePoolQuery($groupId)
                ->inRandomOrder()
                ->take($n)
                ->pluck('id')
                ->all();
        }

        if ($mode === 'manual') {
            $ordered = $this->filterValidOrderedIds($groupId);
            if ($ordered === null) {
                return null;
            }
            if ($ordered === []) {
                $this->addError('selectedQuestionIds', 'Selecciona al menos una pregunta.');

                return null;
            }
            $this->form->questions_total = count($ordered);

            return $ordered;
        }

        $fixed = $this->filterValidOrderedIds($groupId);
        if ($fixed === null) {
            return null;
        }

        $extra = max(0, (int) $this->randomExtraCount);

        if ($fixed === [] && $extra === 0) {
            $this->addError('selectedQuestionIds', 'Elige preguntas fijas y/o indica cuántas aleatorias quieres.');

            return null;
        }

        $randomBase = $this->basePoolQuery($groupId);
        if ($fixed !== []) {
            $randomBase->whereNotIn('id', $fixed);
        }

        $availableForRandom = $randomBase->count();

        if ($extra > $availableForRandom) {
            $this->addError(
                'randomExtraCount',
                "Solo hay {$availableForRandom} pregunta(s) disponible(s) para sortear (sin contar las fijas)."
            );

            return null;
        }

        $randomIds = $extra > 0
            ? $randomBase->clone()->inRandomOrder()->take($extra)->pluck('id')->all()
            : [];

        $this->form->questions_total = count($fixed) + count($randomIds);

        return array_merge($fixed, $randomIds);
    }

    protected function basePoolQuery(int $groupId): Builder
    {
        return Question::query()
            ->where('question_group_id', $groupId)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->whereHas('group', fn ($gq) => $gq->accessibleFor(Auth::user()));
    }

    /**
     * @return array<int>|null
     */
    protected function filterValidOrderedIds(int $groupId): ?array
    {
        $ids = array_values(array_unique(array_map('intval', $this->selectedQuestionIds)));

        if ($ids === []) {
            return [];
        }

        $validRows = Question::query()
            ->where('question_group_id', $groupId)
            ->whereIn('id', $ids)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->whereHas('group', fn ($gq) => $gq->accessibleFor(Auth::user()))
            ->pluck('id')
            ->all();

        $validSet = array_flip($validRows);

        $ordered = [];
        foreach ($ids as $id) {
            if (! isset($validSet[$id])) {
                $this->addError('selectedQuestionIds', 'Hay preguntas inválidas o que no pertenecen a esta categoría.');

                return null;
            }
            $ordered[] = $id;
        }

        return $ordered;
    }

    public function toggleActive(GameSession $gameSession)
    {
        $gameSession->update(['is_active' => ! $gameSession->is_active]);
    }

    public function endSession(GameSession $gameSession)
    {
        $gameSession->update([
            'is_active' => false,
            'is_running' => false,
            'is_paused' => false,
        ]);
    }

    public function run(GameSession $gameSession)
    {
        if ($gameSession->sessionQuestions()->count() === 0) {
            session()->flash('error', 'Configura las preguntas de la partida antes de ejecutar.');

            return $this->redirect(route('sessions.index'));
        }

        return $this->redirect(route('sessions.run', $gameSession));
    }

    public function render()
    {
        $sessions = GameSession::query()
            ->latest()
            ->withCount('sessionQuestions')
            ->paginate(10);

        $questionGroups = QuestionGroup::query()
            ->accessibleFor(Auth::user())
            ->orderBy('name')
            ->get();

        $compositionSession = $this->compositionSessionId
            ? GameSession::query()->find($this->compositionSessionId)
            : null;

        $poolQuestions = collect();
        $poolAvailableCount = 0;

        if ($this->compositionSessionId && $this->compositionQuestionGroupId) {
            $gid = (int) $this->compositionQuestionGroupId;
            $poolAvailableCount = $this->basePoolQuery($gid)->count();

            $rows = $this->basePoolQuery($gid)
                ->orderBy('id')
                ->get(['id', 'statement', 'qtype']);

            if ($this->poolQuestionSearch !== '') {
                $needle = Str::lower($this->poolQuestionSearch);
                $rows = $rows->filter(fn ($row) => Str::contains(Str::lower(strip_tags($row->statement)), $needle));
            }

            $poolQuestions = $rows->values();
        }

        return view('livewire.manage-sessions', compact(
            'sessions',
            'questionGroups',
            'poolQuestions',
            'poolAvailableCount',
            'compositionSession'
        ))
            ->layout('layouts.adminlte', [
                'title' => 'Partidas',
                'header' => 'Gestionar Partidas',
            ]);
    }
}
