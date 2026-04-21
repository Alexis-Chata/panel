@php
    $timer = $current ? $current->timer_override ?? $gameSession->timer_default : 0;
@endphp

<div id="play-basic-root">
    {{-- 1) No hay pregunta actual --}}
    @if (!$current)
        <div class="card">
            <div class="card-body text-center">
                <span class="badge badge-secondary mb-2">
                    Q #{{ min($gameSession->current_q_index + 1, $gameSession->questions_total) }} /
                    {{ $gameSession->questions_total }}
                </span>

                @if (!$gameSession->is_active)
                    {{-- Partida finalizada --}}
                    <h5 class="mb-2">¡Partida finalizada!</h5>
                    <a class="btn btn-primary btn-sm" href="{{ route('winners', $gameSession) }}">
                        Ver ganadores
                    </a>
                @else
                    {{-- Aún no inicia o el docente está por lanzar la primera --}}
                    <h5 class="mb-2">Esperando pregunta…</h5>
                @endif
            </div>
        </div>

        {{-- 2) La partida existe pero AÚN NO ha iniciado --}}
    @elseif(!$gameSession->is_running)
        <div class="card lobby-card card-lift card-lift--dark">
            <div class="card-body text-center" wire:poll.4s.keep-alive="refreshRoster">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <span class="badge badge-secondary">
                        Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
                    </span>
                    <span class="lobby-badge">Sala de espera</span>
                    <span class="badge badge-info">
                        <i class="fas fa-users mr-1"></i>{{ count($roster) }}
                    </span>
                </div>

                <h5 class="mb-1">Esperando que el docente inicie…</h5>
                <div class="text-muted mb-3">Mantente listo. El juego comenzará en breve.</div>

                {{-- Grilla de avatares mejorada --}}
                <div class="avatar-grid">
                    @forelse($roster as $p)
                        <div class="avatar-item text-center" data-pid="{{ $p['id'] }}" style="width:96px">
                            <div class="avatar-outer mb-1" data-toggle="tooltip" title="{{ $p['name'] }}">
                                <img src="{{ $p['photo_url'] }}" class="avatar-photo" alt="{{ $p['name'] }}"
                                    loading="lazy">
                                <span class="presence-dot" aria-hidden="true"></span> {{-- se vuelve “verde” si usas presence --}}
                            </div>
                            <div class="avatar-name text-truncate small">{{ $p['first_name'] }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Aún no hay participantes conectados.</div>
                    @endforelse
                </div>

                <div class="mt-3 small text-muted">
                    {{ count($roster) }} participante{{ count($roster) === 1 ? '' : 's' }}
                    conectado{{ count($roster) === 1 ? '' : 's' }}
                </div>
            </div>
        </div>

        {{-- 3) La partida está corriendo --}}
    @else
        <div wire:key="q-{{ $gameSession->id }}-{{ $gameSession->current_q_index }}-{{ optional($gameSession->current_q_started_at)->timestamp ?? 'none' }}"
            data-card-timer="{{ $gameSession->id }}:{{ $gameSession->current_q_index }}"
            data-total="{{ (int) $timer }}" data-left="{{ $secondsLeft }}"
            data-running="{{ $gameSession->is_running ? 'true' : 'false' }}"
            data-paused="{{ $gameSession->is_paused ? 'true' : 'false' }}"
            data-answered="{{ $answered_any ? 'true' : 'false' }}"
            class="card question-card card-lift card-lift--dark">

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge badge-secondary">
                        Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
                    </span>
                    <span class="badge {{ $gameSession->is_paused ? 'badge-warning' : 'badge-success' }}">
                        {{ $gameSession->is_paused ? 'Pausa' : 'En curso' }}
                    </span>
                    <span class="badge badge-dark">
                        ⏱ <span data-seconds>{{ (int) $secondsLeft }}</span>s
                    </span>
                </div>

                {{-- Barra de tiempo --}}
                <div class="progress timebar mb-3" aria-label="Tiempo restante">
                    <div class="progress-bar" role="progressbar"
                        style="width: {{ (int) $timer > 0 ? round(($secondsLeft / max($timer, 1)) * 100) : 0 }}%"
                        data-progress>
                    </div>
                </div>

                @if ($gameSession->student_view_mode === 'full')
                    <h5 class="mb-3">{{ $current->question->statement }}</h5>
                @endif

                {{-- Alternativas (solo si no es short) --}}
                @if ($current->question->qtype !== 'short')
                    <div class="options-wrap">
                        @foreach ($current->question->options->sortBy('opt_order') as $opt)
                            @php
                                $isSelected = $answered_option_id === $opt->id;
                                $isCorrect = (bool) $opt->is_correct;
                                $showCorrect = $gameSession->is_paused && $isCorrect;
                                $showWrong = $gameSession->is_paused && $isSelected && !$isCorrect;
                            @endphp

                            <button
                                class="option-card list-group-item list-group-item-action d-flex align-items-center
                                {{ $isSelected ? 'selected' : '' }}
                                {{ $showCorrect ? 'is-correct' : '' }}
                                {{ $showWrong ? 'is-wrong' : '' }}"
                                wire:click="answer({{ $opt->id }}, 0)" wire:loading.attr="disabled"
                                wire:target="answer">
                                <span class="option-bubble mr-3">{{ $opt->label }}</span>
                                <span class="option-text flex-fill text-left">{{ $opt->content }}</span>

                                {{-- Estado visual derecha --}}
                                @if ($showCorrect)
                                    <span class="right-pill"><i class="fas fa-check"></i></span>
                                @elseif ($showWrong)
                                    <span class="right-pill wrong"><i class="fas fa-times"></i></span>
                                @elseif ($isSelected)
                                    <span class="right-pill neutral"><i class="fas fa-dot-circle"></i></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Respuesta corta --}}
                @if ($current->question->qtype === 'short')
                    <div class="input-group">
                        <input class="form-control" placeholder="Escribe tu respuesta" wire:model.defer="respuesta"
                            wire:keydown.enter="enviarRespuestaCorta">
                        <div class="input-group-append">
                            <button class="btn btn-primary" wire:click="enviarRespuestaCorta"
                                wire:loading.attr="disabled">
                                Enviar
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Feedback y estados inferiores (usa answered_any) --}}
                @php $hasAnswered = $answered_any; @endphp

                @if ($hasAnswered && $gameSession->is_paused)
                    @if ($current->feedback_override ?? $current->question->feedback)
                        <div class="alert alert-info mt-3">
                            {!! nl2br(e($current->feedback_override ?? $current->question->feedback)) !!}
                        </div>
                    @endif
                @endif

                @if ($hasAnswered && !$gameSession->is_paused)
                    <div class="alert alert-secondary mt-3 mb-0">
                        Respuesta registrada. Espera a que finalicen todos…
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        (function() {
            const sid = @json($gameSession->id);
            const key = 'play-auto-' + sid;

            window.__panelSubs ??= {};
            if (window.__panelSubs[key]) return;
            window.__panelSubs[key] = true;

            function getCompId() {
                const root = document.querySelector('#play-basic-root[wire\\:id]') ||
                    document.querySelector('#play-basic-root [wire\\:id]');
                const any = root || document.querySelector('[wire\\:id]');
                return any ? any.getAttribute('wire:id') : null;
            }

            (function boot() {
                if (!window.Echo || !window.Livewire) return setTimeout(boot, 80);
                const compId = getCompId();
                if (!compId) return setTimeout(boot, 80);

                const callSafe = (method) => {
                    const comp = Livewire.find(compId);
                    if (comp && typeof comp.call === 'function') comp.call(method);
                };

                // Suscríbete al canal privado y llama métodos del componente directamente
                try {
                    window.Echo.private(`session.${sid}`)
                        .listen('.GameSessionStateChanged', () => callSafe('syncState'))
                        .listen('.AnswerSubmitted', () => callSafe('refreshStats'))
                        .listen('.ParticipantJoined', () => callSafe('refreshRoster'))
                        .listen('.ParticipantLeft', () => callSafe('refreshRoster'));
                } catch (e) {
                    console.warn('Suscripción WS falló, reintentando...', e);
                    window.__panelSubs[key] = false;
                    setTimeout(boot, 300);
                    return;
                }
            })();
        })();
    </script>
    <script>
        (function() {
            function initAllTimers() {
                document.querySelectorAll('[data-card-timer]').forEach((card) => initTimerFor(card));
            }

            function getCompId() {
                const root = document.querySelector('#play-basic-root[wire\\:id]') ||
                    document.querySelector('#play-basic-root [wire\\:id]');
                const any = root || document.querySelector('[wire\\:id]');
                return any ? any.getAttribute('wire:id') : null;
            }

            function callWire(fn, ...args) {
                try {
                    const id = getCompId();
                    if (!id) return;
                    const comp = window.Livewire && window.Livewire.find ? window.Livewire.find(id) : null;
                    if (comp && typeof comp.call === 'function') comp.call(fn, ...args);
                } catch (e) {
                    console.warn('LW call falló:', e);
                }
            }

            function initTimerFor(card) {
                if (card.__timerBound) return;
                card.__timerBound = true;

                const secEl = card.querySelector('[data-seconds]');
                const barEl = card.querySelector('[data-progress]');

                const state = {
                    seconds: parseInt(card.getAttribute('data-left') || '0', 10),
                    running: card.getAttribute('data-running') === 'true' && card.getAttribute('data-paused') !==
                        'true',
                    answered: card.getAttribute('data-answered') === 'true',
                    total: parseInt(card.getAttribute('data-total') || '0', 10),
                    int: null
                };

                function render() {
                    if (secEl) secEl.textContent = Math.max(0, state.seconds);
                    if (barEl && state.total > 0) {
                        const p = Math.round((Math.max(0, state.seconds) / state.total) * 100);
                        barEl.style.width = Math.max(0, Math.min(100, p)) + '%';
                    }
                }

                function tick() {
                    if (!state.running || state.answered) return;
                    if (state.seconds > 0) {
                        state.seconds--;
                        render();
                        if (state.seconds <= 0 && !state.answered) {
                            // Auto-responder al vencer tiempo (registra "sin respuesta")
                            callWire('answer', null, 0);
                            state.answered = true;
                            stop();
                        }
                    }
                }

                function start() {
                    stop();
                    state.int = setInterval(tick, 1000);
                }

                function stop() {
                    if (state.int) {
                        clearInterval(state.int);
                        state.int = null;
                    }
                }

                function syncFromAttrs() {
                    const left = parseInt(card.getAttribute('data-left') || '0', 10);
                    const srvRun = card.getAttribute('data-running') === 'true';
                    const srvPause = card.getAttribute('data-paused') === 'true';
                    const answered = card.getAttribute('data-answered') === 'true';
                    const total = parseInt(card.getAttribute('data-total') || '0', 10);

                    state.running = srvRun && !srvPause;
                    state.answered = answered;
                    state.total = Number.isNaN(total) ? state.total : total;

                    if (!Number.isNaN(left)) {
                        // Adoptar SIEMPRE el valor que viene del servidor (suba o baje)
                        if (Number.isNaN(state.seconds) || left !== state.seconds) {
                            state.seconds = left;
                        }
                    }

                    // Si cambió el total, fuerza re-cálculo de la barra
                    if (!Number.isNaN(total) && total !== state.total) {
                        state.total = total;
                    }


                    if (state.running && !state.int) start();
                    if (!state.running && state.int) stop();
                    render();
                }

                // Arranque inicial
                render();
                if (state.seconds > 0 && state.running) start();

                // Observa cambios de atributos
                const attrObs = new MutationObserver(syncFromAttrs);
                attrObs.observe(card, {
                    attributes: true,
                    attributeFilter: ['data-left', 'data-running', 'data-paused', 'data-answered', 'data-total']
                });

                // Limpieza al remover el card
                const removalObs = new MutationObserver(() => {
                    if (!document.body.contains(card)) {
                        stop();
                        attrObs.disconnect();
                        removalObs.disconnect();
                    }
                });
                removalObs.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                card.__timerState = state;
            }

            initAllTimers();
            const bodyObs = new MutationObserver(() => initAllTimers());
            bodyObs.observe(document.body, {
                childList: true,
                subtree: true
            });
        })();
    </script>
@endpush
