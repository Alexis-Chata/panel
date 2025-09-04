<div x-data
    x-on:countdown.window="
        let el = document.getElementById('countdown-box');
        el.classList.remove('d-none');
        el.innerText = '3';
        setTimeout(()=>{ el.innerText='2'; }, 700);
        setTimeout(()=>{ el.innerText='1'; }, 1400);
        setTimeout(()=>{
            el.classList.add('d-none');
            Livewire.dispatch('advanceNow');
        }, 2100);
     ">
    <div id="countdown-box" class="display-3 text-center d-none"
        style="position:fixed;top:30%;left:0;right:0;z-index:9999;">
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge badge-secondary">Q #{{ $gameSession->current_q_index + 1 }} /
                        {{ $gameSession->questions_total }}</span>
                    <span class="badge {{ $gameSession->is_paused ? 'badge-warning' : 'badge-success' }}">
                        {{ $gameSession->is_paused ? 'Pausa' : ($gameSession->is_running ? 'En curso' : 'Listo') }}
                    </span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary btn-sm" wire:click="start"><i class="fas fa-play mr-1"></i>
                        Iniciar</button>
                    <button class="btn btn-outline-secondary btn-sm" wire:click="togglePause">
                        {{ $gameSession->is_paused ? 'Reanudar' : 'Pausar' }}
                    </button>
                    <button class="btn btn-outline-info btn-sm" wire:click="revealAndPause">
                        <i class="fas fa-lightbulb mr-1"></i> Revelar + Pausa
                    </button>
                    <button class="btn btn-primary btn-sm" wire:click="nextQuestion">
                        Siguiente <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <hr>

            {{-- MÉTRICAS EN VIVO --}}
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap align-items-center">
                                <div class="mr-3 mb-2">
                                    <span class="badge badge-primary">Participantes: {{ $pCount }}</span>
                                </div>
                                <div class="mr-3 mb-2">
                                    <span class="badge badge-info">Respondidos: {{ $answered }} /
                                        {{ $pCount }}</span>
                                </div>
                                <div class="mr-3 mb-2">
                                    <span class="badge badge-success">% Acierto:
                                        {{ $answered ? number_format(($corrects / $answered) * 100, 1) : 0 }}%
                                    </span>
                                </div>
                            </div>

                            @if (!empty($dist))
                                <div class="mt-2">
                                    <div class="d-flex flex-wrap">
                                        @foreach ($dist as $d)
                                            <div class="mr-2 mb-2">
                                                <span
                                                    class="badge {{ $d['is_correct'] ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ $d['label'] }}: {{ $d['count'] }}
                                                    @if ($d['is_correct'])
                                                        ✓
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body py-2">
                            <h6 class="mb-2">Mini-ranking (Top 5)</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($top as $i => $p)
                                    <li class="list-group-item py-1 d-flex justify-content-between">
                                        <div>
                                            <strong>#{{ $i + 1 }}</strong>
                                            {{ $p->nickname ?? $p->user?->name }}
                                        </div>
                                        <div>
                                            <span class="badge badge-success">{{ $p->score }}</span>
                                            <span
                                                class="badge badge-dark">{{ number_format($p->time_total_ms / 1000, 2) }}s</span>
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item py-1">Sin datos</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @if ($current && $current->question)
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3">Pregunta</h5>
                        <p class="lead">{{ $current->question->statement }}</p>
                        <div class="small text-muted">
                            Tiempo por pregunta: <strong>{{ $current->timer_override ?? $gameSession->timer_default }}
                                s</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Alternativas</h6>
                        <ul class="list-group">
                            @foreach ($current->question->options->sortBy('opt_order') as $opt)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><strong>{{ $opt->label }}.</strong> {{ $opt->content }}</div>
                                    @if ($gameSession->is_paused && $opt->is_correct)
                                        <span class="badge badge-success">Correcta</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($gameSession->is_paused && ($current->feedback_override ?? $current->question->feedback))
                            <div class="alert alert-info mt-3">
                                {!! nl2br(e($current->feedback_override ?? $current->question->feedback)) !!}
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-0">No hay pregunta en el índice actual.</div>
            @endif
        </div>
    </div>
</div>
