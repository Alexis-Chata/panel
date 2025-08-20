<?php

namespace App\Livewire\Player;

use App\Actions\AnswerQuestion;
use App\Models\AssignedQuestion;
use App\Models\GameSession;
use App\Models\QuestionOption;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.adminlte-livewire')]
class PlaySession extends Component
{
    public function render()
    {
        return view('livewire.player.play-session')->title("Jugar — {$this->session->title}");
    }

    public GameSession $session;
    public SessionParticipant $participant;

    public ?AssignedQuestion $current = null;
    public ?int $selectedOptionId = null;
    public ?string $freeText = null; // para numeric/text
    public bool $busy = false;

    public function mount(GameSession $session)
    {
        $this->session = $session;

        // Garantiza que el usuario esté unido a esta sesión
        $this->participant = SessionParticipant::where('game_session_id', $session->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->loadCurrent();
    }

    public function loadCurrent(): void
    {
        $this->current = AssignedQuestion::query()
            ->with(['question.options' => fn($q) => $q->orderBy('order')])
            ->where('game_session_id', $this->session->id)
            ->where('participant_id', $this->participant->id)
            ->whereDoesntHave('answer')
            ->orderBy('phase')->orderBy('order')
            ->first();

        // limpia selección para la siguiente pregunta
        $this->selectedOptionId = null;
        $this->freeText = null;
    }

    public function answer(): void
    {
        if (!$this->current) return;
        if ($this->busy) return; // evita doble click

        $this->busy = true;

        try {
            $type = $this->current->question->type;

            $option = null;
            $freeText = null;

            if (in_array($type, ['single', 'boolean'])) {
                if (!$this->selectedOptionId) {
                    $this->dispatch('swal', title: 'Selecciona una opción', icon: 'warning');
                    return;
                }

                // Garantiza que la opción elegida pertenece a la pregunta
                $option = QuestionOption::where('id', $this->selectedOptionId)
                    ->where('question_id', $this->current->question_id)
                    ->first();

                if (!$option) {
                    $this->dispatch('swal', title: 'Opción inválida', icon: 'error');
                    return;
                }
            } elseif (in_array($type, ['numeric', 'text'])) {
                if ($this->freeText === null || $this->freeText === '') {
                    $this->dispatch('swal', title: 'Completa tu respuesta', icon: 'warning');
                    return;
                }
                $freeText = (string) $this->freeText;
            } else {
                $this->dispatch('swal', title: 'Tipo de pregunta no soportado aún', icon: 'info');
                return;
            }

            $ans = app(AnswerQuestion::class)->handle(
                $this->current,
                $option,
                $freeText,
                0 // TODO: medir response_ms desde JS si luego agregamos timers
            );

            $this->dispatch(
                'swal',
                title: $ans->is_correct ? '¡Correcto!' : 'Incorrecto',
                icon: $ans->is_correct ? 'success' : 'error'
            );

            $this->loadCurrent();          // pasa a la siguiente (si existe)
            $this->participant->refresh(); // refresca puntaje local
        } finally {
            $this->busy = false; // siempre libera
        }
    }

    #[On('phase-changed')]
    public function onPhaseChanged(): void
    {
        $this->busy = false;      // por si llegó durante un envío
        $this->session->refresh();
        $this->loadCurrent();
    }

    #[On('score-updated')]
    public function onScoreUpdated(): void
    {
        $this->participant->refresh();
    }
}
