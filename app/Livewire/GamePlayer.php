<?php

namespace App\Livewire;

use App\Events\ScoreUpdated;
use App\Models\AssignedQuestion;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use Livewire\Component;

class GamePlayer extends Component
{
    public function render()
    {
        return view('livewire.game-player');
    }

    public GameSession $session;
    public SessionParticipant $participant;
    public AssignedQuestion $currentQuestion;
    public int $currentPhase = 0;
    public array $scores = [];

    protected $listeners = [
        'scoreUpdated' => 'handleScoreUpdate',
        'phaseChanged' => 'handlePhaseChanged',
    ];

    public function mount(GameSession $session, SessionParticipant $participant)
    {
        $this->session = $session;
        $this->participant = $participant;
        $this->currentPhase = $session->current_phase;
        $this->loadNextQuestion();
        $this->loadScores();
    }

    public function loadNextQuestion()
    {
        $q = $this->participant->assignedQuestions()
            ->where('phase', $this->currentPhase)
            ->whereNull('answered_at')
            ->orderBy('order')
            ->first();

        $this->currentQuestion = $q;
    }

    public function answer($optionId = null, $freeText = null)
    {
        // 1. Guardar la respuesta
        $action = new \App\Actions\AnswerQuestion;
        $answer = $action->handle(
            $this->currentQuestion,
            $optionId ? \App\Models\QuestionOption::find($optionId) : null,
            $freeText,
            0 // en un caso real usarías `request()->timerMs`
        );

        // 2. Emite ScoreUpdated
        event(new ScoreUpdated(
            $this->session->id,
            $this->participant->id,
            $this->participant->total_score
        ));

        // 3. Cargar siguiente pregunta
        $this->loadNextQuestion();
    }

    public function handleScoreUpdate($data)
    {
        if ($data['participant_id'] == $this->participant->id) {
            $this->participant->refresh();
        }
        $this->loadScores();
    }

    public function handlePhaseChanged($data)
    {
        $this->currentPhase = $data['currentPhase'];
        $this->loadNextQuestion();
        $this->loadScores();
    }

    private function loadScores()
    {
        $this->scores = $this->session->participants()
            ->with('user')
            ->orderByDesc('total_score')
            ->get()
            ->toArray();
    }
}
