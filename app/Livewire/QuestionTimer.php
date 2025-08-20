<?php

namespace App\Livewire;

use App\Models\AssignedQuestion;
use Livewire\Component;

class QuestionTimer extends Component
{
    public function render()
    {
        return view('livewire.question-timer');
    }

    public AssignedQuestion $aq;
    public int $secondsLeft = 30; // ejemplo

    protected $listeners = [
        'timeUp' => 'handleTimeUp',
    ];

    public function mount(AssignedQuestion $aq)
    {
        $this->aq = $aq;
    }

    public function handleTimeUp()
    {
        // Si el tiempo se acabó, guardar respuesta vacía
        // (puedes decidir la lógica que quieras)
    }
}
