{{-- Pantalla full-screen de exhibición (sin menú, sin interacción, sin wire:poll) --}}
<div id="screen-root" class="screen d-flex align-items-center justify-content-center p-4" {{-- Datos para el reloj (leídos por JS, se actualizan al re-render Livewire) --}}
    data-started="{{ optional($gameSession->current_q_started_at)->toIso8601String() }}"
    data-duration="{{ (int) ($current?->timer_override ?? $gameSession->timer_default) }}"
    data-paused="{{ $gameSession->is_paused ? 1 : 0 }}" data-running="{{ $gameSession->is_running ? 1 : 0 }}"
    data-index="{{ $gameSession->current_q_index }}">

    {{-- Estilos mínimos (Bootstrap 4.6 ya está en el layout fullscreen) --}}
    <style>
        .screen {
            min-height: 100vh;
            width: 100vw;
        }

        .muted {
            opacity: .75;
        }

        .q-index {
            opacity: .65;
            letter-spacing: .06em;
        }

        .q-title {
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .2px;
            font-size: clamp(26px, 6vh, 64px);
        }

        .opt {
            border: 2px solid rgba(255, 255, 255, .18);
            border-radius: 18px;
            padding: clamp(12px, 2.2vh, 28px);
            background: rgba(255, 255, 255, .03);
        }

        .opt+.opt {
            margin-top: 1rem;
        }

        .opt-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: clamp(36px, 6vh, 56px);
            height: clamp(36px, 6vh, 56px);
            border-radius: 12px;
            font-weight: 800;
            background: rgba(255, 255, 255, .12);
            font-size: clamp(18px, 3vh, 28px);
        }

        .opt-text {
            font-size: clamp(18px, 4vh, 40px);
            line-height: 1.3;
        }

        .opt-correct {
            border-color: rgba(0, 255, 143, .65);
            background: rgba(0, 255, 143, .08);
            box-shadow: 0 0 0 2px rgba(0, 255, 143, .15) inset;
        }

        .badge-lg {
            font-size: clamp(12px, 2.2vh, 20px);
            padding: .5em .8em;
        }

        .timer-chip {
            background: rgba(255, 255, 255, .08);
            border: 2px solid rgba(255, 255, 255, .18);
            border-radius: 14px;
            padding: .35rem .75rem;
            backdrop-filter: blur(4px);
        }

        .timer-text {
            font-weight: 800;
            font-size: clamp(18px, 4vh, 40px);
            letter-spacing: .02em;
        }

        .timer-icon {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #17a2b8;
            display: inline-block;
        }
    </style>
    @php $q = $current?->question; @endphp

    @if (!$gameSession->is_running || !$current || !$q)
        {{-- Modo espera: sin cronómetro --}}
        <div class="text-center w-100">
            <div class="q-index mb-2 small text-uppercase">
                Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
            </div>
            <div class="q-title mb-1">Esperando que el docente inicie…</div>
            <div class="muted">Mantente listo. El juego comenzará en breve.</div>
        </div>
    @else
        {{-- Pregunta + cronómetro + alternativas --}}
        <div class="container-fluid position-relative">

            {{-- Cronómetro arriba a la derecha --}}
            <div class="position-absolute" style="top:12px; right:12px;">
                <div class="timer-chip d-inline-flex align-items-center">
                    <span class="timer-icon mr-2"></span>
                    <span id="timerValue" class="timer-text">--:--</span>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-8 text-center mb-4">
                    <div class="q-title">
                    <div class="ck-content" wire:ignore>
                        {!! $q->statement !!}
                    </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="row">
                    @foreach ($q->options as $opt)
                        <div class="col-12 col-lg-6 mb-3">
                        <div class="opt h-100 d-flex align-items-start justify-content-between {{ $gameSession->is_paused && $opt->is_correct ? 'opt-correct' : '' }}">
                            <div class="d-flex">
                            <span class="opt-badge mr-3 text-white">{{ $opt->label }}</span>
                            <span class="opt-text">{{ $opt->content }}</span>
                            </div>
                            @if ($gameSession->is_paused && $opt->is_correct)
                            <span class="badge badge-success badge-lg align-self-center">Correcta</span>
                            @endif
                        </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        (function() {
            const sid = @json($gameSession->id);
            const subKey = 'screen-' + sid;

            // Evita dobles suscripciones
            window.__panelSubs ??= {};
            if (window.__panelSubs[subKey]) return;
            window.__panelSubs[subKey] = true;

            // ===== Cronómetro (cliente, sin wire:poll) =====
            if (!window.__screenTimers) window.__screenTimers = {};
            (function startClock() {
                if (window.__screenTimers[sid]) return; // ya corriendo

                let lastLeft = null;
                let lastIndex = null;

                function fmt(sec) {
                    sec = Math.max(0, Math.floor(sec));
                    const m = Math.floor(sec / 60).toString().padStart(2, '0');
                    const s = (sec % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                }

                function tick() {
                    const root = document.getElementById('screen-root');
                    const out = document.getElementById('timerValue');
                    if (!root || !out) return;

                    const running = root.dataset.running === '1';
                    const paused = root.dataset.paused === '1';
                    const dur = parseInt(root.dataset.duration || '0', 10);
                    const idxStr = root.dataset.index || '';
                    const started = root.dataset.started;

                    // si cambió la pregunta, resetea cache
                    if (lastIndex !== idxStr) {
                        lastIndex = idxStr;
                        lastLeft = null;
                    }

                    let left = dur;

                    if (!running || !started) {
                        // muestra duración total si aún no corre
                        left = dur;
                    } else if (paused) {
                        // congelar en el último valor conocido
                        if (lastLeft === null) {
                            const t0 = Date.parse(started);
                            const elapsed = Math.max(0, (Date.now() - t0) / 1000);
                            left = Math.max(0, Math.round(dur - elapsed));
                            lastLeft = left;
                        } else {
                            left = lastLeft;
                        }
                    } else {
                        // corriendo
                        const t0 = Date.parse(started);
                        const elapsed = Math.max(0, (Date.now() - t0) / 1000);
                        left = Math.max(0, Math.round(dur - elapsed));
                        lastLeft = left;
                    }

                    out.textContent = fmt(left);
                }

                window.__screenTimers[sid] = setInterval(tick, 500);
                tick();
            })();

            // ===== Suscripción WS y refresco de estado =====
            function bootWs() {
                const ok = !!(window.Livewire && window.Echo);
                const root = document.querySelector('#screen-root');
                const compId = root && root.getAttribute('wire:id');
                if (!ok || !compId) return setTimeout(bootWs, 100);

                const call = (method) => {
                    const comp = Livewire.find(compId);
                    if (comp && typeof comp.call === 'function') comp.call(method);
                };

                try {
                    window.Echo.private(`session.${sid}`)
                        .listen('.GameSessionStateChanged', () => call('syncState'))
                        .listen('.AnswerSubmitted', () => call('syncState'));
                } catch (e) {
                    window.__panelSubs[subKey] = false;
                    setTimeout(bootWs, 400);
                }
            }
            bootWs();
        })();
    </script>
@endpush

