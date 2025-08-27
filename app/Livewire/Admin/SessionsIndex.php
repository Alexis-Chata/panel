<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\GameSessionForm;
use App\Models\GameSession;
use App\Models\QuestionPool;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.adminlte-livewire')]
#[Title('Partidas')]
class SessionsIndex extends Component
{
    use WithPagination;

    // Formulario
    public GameSessionForm $form;

    // Control UI
    public bool $showForm = false;

    #[Url] public string $q = '';          // búsqueda por código/título (query string)
    #[Url] public string $status = '';     // filtro por estado (query string)

    public int $perPage = 10;
    protected $paginationTheme = 'bootstrap'; // AdminLTE usa Bootstrap

    /** @var \Illuminate\Support\Collection */
    public $questionPools;

    public function mount()
    {
        // carga catálogos para los selects
        $this->questionPools = QuestionPool::select('id', 'name')->orderBy('name')->get();
    }

    // Abrir/cerrar form
    public function openCreateForm(): void
    {
        $this->form->resetToDefaults();
        $this->showForm = true;

        // opcional: agregar una fila por defecto en fase 1
        if (empty($this->form->pools[1])) {
            $this->form->addPoolRow(1);
        }
    }

    public function cancelCreate(): void
    {
        $this->showForm = false;
    }

    // Guardar
    public function save(): void
    {
        $session = $this->form->store();

        // Cerrar form y redirigir a lobby
        $this->showForm = false;
        $this->dispatch('sessions-index-refresh');
        redirect()->route('admin.sessions.lobby', $session->id);
    }

    public function updatingQ()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }

    #[On('sessions-index-refresh')]
    public function realtimeRefresh(): void
    {
        $this->dispatch('$refresh');
    }

    public function addPoolRow(int $phase): void
    {
        $this->form->addPoolRow($phase);
    }

    public function removePoolRow(int $phase, int $index): void
    {
        $this->form->removePoolRow($phase, $index);
    }

    public function render()
    {
        $sessions = GameSession::query()
            ->when($this->q !== '', function ($q) {
                $term = trim($this->q);
                $q->where(function ($qq) use ($term) {
                    $qq->where('code', 'like', $term . '%')
                        ->orWhere('title', 'like', '%' . $term . '%');
                });
            })
            ->when($this->status !== '', fn($q) => $q->where('status', $this->status))
            ->withCount('participants')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.sessions-index', compact('sessions'));
    }
}
