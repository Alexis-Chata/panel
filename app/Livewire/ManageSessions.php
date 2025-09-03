<?php

namespace App\Livewire;

use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionQuestion;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageSessions extends Component
{
    public $title = '';
    public $questions_total = 10;
    public $timer_default = 30;
    public $student_view_mode = 'full'; // full | choices_only

    protected function rules()
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'questions_total' => ['required', 'integer', 'min:1', 'max:50'],
            'timer_default' => ['required', 'integer', 'min:5', 'max:600'],
            'student_view_mode' => ['required', Rule::in(['full', 'choices_only'])],
        ];
    }

    public function createSession()
    {
        $this->validate();

        $session = GameSession::create([
            'code' => Str::upper(Str::random(6)),
            'title' => $this->title ?: 'Partida',
            'phase_mode' => 'basic',
            'questions_total' => $this->questions_total,
            'timer_default' => $this->timer_default,
            'student_view_mode' => $this->student_view_mode,
            'is_active' => false,
            'is_running' => false,
            'current_q_index' => 0,
            'is_paused' => false,
        ]);

        // Autoseleccionar N preguntas aleatorias si hay banco suficiente
        $qs = Question::inRandomOrder()->take($this->questions_total)->get();
        $i = 0;
        foreach ($qs as $q) {
            SessionQuestion::create([
                'game_session_id' => $session->id,
                'question_id' => $q->id,
                'q_order' => $i++,
            ]);
        }

        $this->reset(['title', 'questions_total', 'timer_default', 'student_view_mode']);
        session()->flash('ok', "Partida creada: {$session->code}");
    }

    public function toggleActive(GameSession $gameSession)
    {
        // Solo una activa a la vez (opcional)
        if (!$gameSession->is_active) {
            GameSession::where('is_active', true)->update(['is_active' => false]);
        }
        $gameSession->update(['is_active' => !$gameSession->is_active]);
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
        return redirect()->route('sessions.run', $gameSession);
    }

    public function render()
    {
        $sessions = GameSession::latest()->paginate(10);

        return view('livewire.manage-sessions', compact('sessions'))
            ->layout('layouts.adminlte', [
                'title' => 'Partidas',
                'header' => 'Gestionar Partidas',
            ]);
    }
}
