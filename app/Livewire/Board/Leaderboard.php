<?php

namespace App\Livewire\Board;

use App\Models\AssignedQuestion;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionParticipant;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.adminlte-livewire')]
#[Title('Pizarra en vivo')]
class Leaderboard extends Component
{
    public GameSession $session;
    public int $limit = 10;

    public function mount(GameSession $session)
    {
        $this->session = $session;
    }

    #[On('score-updated')]
    #[On('phase-changed')]
    #[On('participant-updated')]
    public function refreshBoard(): void
    {
        $this->session->refresh();
        $this->dispatch('$refresh');
    }

    public function getTopProperty()
    {
        return SessionParticipant::where('game_session_id', $this->session->id)
            ->orderByDesc('total_score')
            ->orderBy('id')
            ->take($this->limit)
            ->get();
    }

    /**
     * Pregunta “en curso” para mostrar con timer.
     * Fase 3: común a todos.
     * Fase 1: la más frecuente entre las pendientes.
     * Calcula deadline con expires_at o (fallback) available_at + time_limit.
     */
    public function getBoardQuestionProperty(): ?array
    {
        if ($this->session->status === 'phase3') {
            $row = AssignedQuestion::query()
                ->selectRaw('question_id, MIN(`expires_at`) as expires_at, MIN(`available_at`) as avail, MIN(`order`) as ord')
                ->where('game_session_id', $this->session->id)
                ->where('phase', 3)
                ->whereDoesntHave('answer')
                ->groupBy('question_id')
                ->orderBy('ord')
                ->first();

            if ($row) {
                $q = Question::find($row->question_id);
                $deadline = $row->expires_at
                    ? Carbon::parse($row->expires_at)
                    : ($row->avail ? Carbon::parse($row->avail)->addSeconds((int)($q?->time_limit_seconds ?? 20)) : null);

                return [
                    'stem'   => $q?->stem,
                    'expires' => $deadline?->toIso8601String(),
                    'phase'  => 3,
                    'order'  => (int) $row->ord,
                ];
            }
            return null;
        }

        if ($this->session->status === 'phase1') {
            $row = AssignedQuestion::query()
                ->selectRaw('question_id, COUNT(*) as cnt, MIN(`expires_at`) as expires_at, MIN(`available_at`) as avail, MIN(`order`) as ord')
                ->where('game_session_id', $this->session->id)
                ->where('phase', 1)
                ->whereDoesntHave('answer')
                ->groupBy('question_id')
                ->orderByDesc('cnt')
                ->orderBy('ord')
                ->first();

            if ($row) {
                $q = Question::find($row->question_id);
                $deadline = $row->expires_at
                    ? Carbon::parse($row->expires_at)
                    : ($row->avail ? Carbon::parse($row->avail)->addSeconds((int)($q?->time_limit_seconds ?? 20)) : null);

                return [
                    'stem'   => $q?->stem,
                    'expires' => $deadline?->toIso8601String(),
                    'phase'  => 1,
                    'order'  => (int) $row->ord,
                ];
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.board.leaderboard')
            ->title("Pizarra — {$this->session->title}");
    }
}
