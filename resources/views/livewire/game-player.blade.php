<div>
    <h3>Partida: {{ $session->title }} | Fase {{ $session->current_phase }}</h3>

    @if ($currentPhase === 1 || $currentPhase === 2 || $currentPhase === 3)
        @livewire('question-timer', ['aq' => $currentQuestion])
    @else
        <div class="alert alert-success">
            <h4>¡Resultado!</h4>
            <ul>
                @foreach ($scores as $score)
                    <li>{{ $score->user->name }}: {{ $score->total_score }} pts</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
