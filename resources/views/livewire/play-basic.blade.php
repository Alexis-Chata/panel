@php
    $timer = $current ? $current->timer_override ?? $gameSession->timer_default : 0;
@endphp

<div wire:poll.1000ms>
    {{-- 1) Aún no hay pregunta cargada --}}
    @if (!$current)
        <div class="card">
            <div class="card-body text-center">
                <span class="badge badge-secondary mb-2">
                    Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
                </span>
                <h5 class="mb-2">Esperando pregunta…</h5>
                @if (!$gameSession->is_running && !$gameSession->is_active)
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('winners', $gameSession) }}">
                        Ver ganadores
                    </a>
                @endif
            </div>
        </div>

        {{-- 2) La partida existe pero AÚN NO ha iniciado --}}
    @elseif(!$gameSession->is_running)
        <div class="card">
            <div class="card-body text-center">
                <span class="badge badge-secondary mb-2">
                    Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
                </span>
                <h5 class="mb-2">Esperando que el docente inicie…</h5>
                <div class="text-muted">Mantente listo. El juego comenzará en breve.</div>
            </div>
        </div>

        {{-- 3) La partida está corriendo: mostramos la pregunta --}}
    @else
        <div x-data="{
            seconds: {{ (int) $timer }},
            running: {{ !$gameSession->is_paused && $gameSession->is_running ? 'true' : 'false' }},
            answered: {{ $answered_option_id ? 'true' : 'false' }},
            startTs: null,
            elapsed() { return this.startTs ? (Date.now() - this.startTs) : 0; },
            tick() {
                if (!this.running || this.answered) return;
                if (this.seconds > 0) { this.seconds--; }
                if (this.seconds <= 0) {
                    this.running = false;
                    if (!this.answered) $wire.answer(null, this.elapsed());
                }
            }
        }" x-init="if (running && !answered) { startTs = Date.now(); }
        setInterval(() => tick(), 1000);" class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge badge-secondary">
                        Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
                    </span>
                    <span class="badge {{ $gameSession->is_paused ? 'badge-warning' : 'badge-success' }}">
                        {{ $gameSession->is_paused ? 'Pausa' : 'En curso' }}
                    </span>
                    <span class="badge badge-dark">
                        ⏱ <span x-text="seconds"></span>s
                    </span>
                </div>

                <hr>

                @if ($gameSession->student_view_mode === 'full')
                    <h5 class="mb-3">{{ $current->question->statement }}</h5>
                @endif

                <div class="list-group">
                    @foreach ($current->question->options->sortBy('opt_order') as $opt)
                        <button
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                        {{ $answered_option_id === $opt->id ? 'active' : '' }}"
                            :disabled="answered || {{ $gameSession->is_paused || !$gameSession->is_running ? 'true' : 'false' }}"
                            @click="if(!answered && {{ !$gameSession->is_paused && $gameSession->is_running ? 'true' : 'false' }}){answered = true;$wire.answer({{ $opt->id }}, elapsed());}">
                            <div><strong>{{ $opt->label }}.</strong> {{ $opt->content }}</div>

                            @if ($gameSession->is_paused && $opt->is_correct)
                                <span class="badge badge-success">Correcta</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @if ($answered_option_id && $gameSession->is_paused)
                    @if ($current->feedback_override ?? $current->question->feedback)
                        <div class="alert alert-info mt-3">
                            {!! nl2br(e($current->feedback_override ?? $current->question->feedback)) !!}
                        </div>
                    @endif
                @endif

                @if ($answered_option_id && !$gameSession->is_paused)
                    <div class="alert alert-secondary mt-3 mb-0">
                        Respuesta registrada. Espera a que finalicen todos…
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
