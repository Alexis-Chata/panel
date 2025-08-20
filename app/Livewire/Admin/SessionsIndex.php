<?php

namespace App\Livewire\Admin;

use App\Models\GameSession;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.adminlte-livewire')]
#[Title('Partidas')]
class SessionsIndex extends Component
{
    use WithPagination;

    public string $title = '';

    #[Url] public string $q = '';          // búsqueda por código/título (query string)
    #[Url] public string $status = '';     // filtro por estado (query string)

    public int $perPage = 10;
    protected $paginationTheme = 'bootstrap'; // AdminLTE usa Bootstrap

    public function updatingQ()
    {
        $this->resetPage();
    }
    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function create(): void
    {
        // Genera código único (6 chars)
        do {
            $code = Str::upper(Str::random(6));
        } while (GameSession::where('code', $code)->exists());

        $s = GameSession::create([
            'code'          => $code,
            'title'         => $this->title ?: 'Nueva partida',
            'status'        => 'lobby',
            'current_phase' => 0,
        ]);

        $this->title = '';
        redirect()->route('admin.sessions.lobby', $s->id);
    }

    public function render()
    {
        $sessions = GameSession::query()
            ->when($this->q !== '', function ($q) {
                $q->where(function ($qq) {
                    $term = trim($this->q);
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
