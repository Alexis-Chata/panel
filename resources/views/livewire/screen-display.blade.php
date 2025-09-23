{{-- Pantalla full-screen de exhibición (sin menú, sin interacción, sin wire:poll) --}}
@php
    $q = $current?->question;
    $finished = !$gameSession->is_active || $gameSession->current_q_index >= $gameSession->questions_total;
@endphp


<div id="screen-root" class="screen d-flex flex-column align-items-stretch justify-content-center p-4" {{-- Datos para el reloj (leídos por JS, se actualizan al re-render Livewire) --}}
    data-started="{{ optional($gameSession->current_q_started_at)->toIso8601String() }}"
    data-duration="{{ (int) ($current?->timer_override ?? $gameSession->timer_default) }}"
    data-paused="{{ $gameSession->is_paused ? 1 : 0 }}" data-running="{{ $gameSession->is_running ? 1 : 0 }}"
    data-index="{{ $gameSession->current_q_index }}" data-total="{{ $gameSession->questions_total }}"
    data-finished="{{ $finished ? 1 : 0 }}">
    <!-- Audio aviso últimos 3s (fijado) -->
    <audio id="audio-warning-3s" wire:ignore src="{{ asset('audio/conteo_regresivo.mp3') }}" preload="auto" playsinline></audio>
    <!-- Audio reveal (ding) cuando se pausa para mostrar la respuesta -->
    <audio id="audio-reveal-ding" wire:ignore src="{{ asset('audio/reveal_ding.wav') }}" preload="auto" playsinline></audio>
    <!-- Overlay de conteo para SCREEN -->
    <div id="screen-countdown"
        class="position-fixed d-none"
        style="z-index:9999; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center;">
    <div class="text-center text-white">
        <div id="screen-countdown-title"
            class="mb-2"
            style="font-weight:800; font-size: clamp(20px, 4vh, 36px); letter-spacing:.02em;">
        ¡Comenzamos!
        </div>
        <div id="screen-countdown-number" class="display-1 font-weight-bold">3</div>
    </div>
    </div>

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

        .results-card {
            border: 2px solid rgba(255, 255, 255, .12);
            border-radius: 16px;
            background: rgba(255, 255, 255, .03);
            padding: 1rem;
        }

        .progress {
            height: clamp(12px, 2vh, 18px);
            background-color: rgba(255, 255, 255, .08);
        }

        .progress-bar {
            font-weight: 700;
        }

        .result-row+.result-row {
            margin-top: .75rem;
        }

        .retro-card {
            border: 2px solid rgba(0, 255, 143, .35);
            background: rgba(0, 255, 143, .06);
            border-radius: 16px;
        }
    </style>

    {{-- ======= PRIORIDAD DE ESTADOS ======= --}}
    @if ($finished)
    <div class="container-fluid w-100 px-0">   {{-- 👈 nuevo wrapper --}}

        <div class="text-center w-100 mb-3">
            <span class="badge badge-secondary mb-2 q-index small text-uppercase">
                Q #{{ min($gameSession->current_q_index + 1, $gameSession->questions_total) }} /
                {{ $gameSession->questions_total }}
            </span>
            <div class="q-title mb-1">¡Partida finalizada!</div>
            <div class="muted">Gracias por participar.</div>
        </div>

        {{-- === PODIO FINAL === --}}
        @if(isset($podium) && $podium->count() > 0)
            @php
                $medal = fn($i) => match($i){0=>'🥇',1=>'🥈',2=>'🥉',default=>'🏅'};
            @endphp

            <div class="row justify-content-center g-3 w-100 mx-0">  {{-- 👈 w-100 + mx-0 --}}
                <div class="col-12 col-xl-10">
                    <div class="results-card">
                        <h5 class="mb-3">Podio final</h5>
                        <div class="row">
                            @foreach($podium as $i => $p)
                                <div class="col-12 col-md-4 mb-3">
                                    <div class="opt h-100 d-flex flex-column align-items-center justify-content-center text-center {{ $i === 0 ? 'podium-first' : '' }}"
                                         style="border-width:3px;">
                                        <div class="display-4 mb-2">{{ $medal($i) }}</div>
                                        <div class="font-weight-bold" style="font-size:clamp(18px,3vh,28px);">
                                            {{ $p->nickname ?? $p->user?->name ?? 'Jugador' }}
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge badge-success badge-lg">Puntaje: {{ $p->score }}</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge badge-dark badge-lg">
                                                Tiempo: {{ number_format(($p->time_total_ms ?? 0) / 1000, 2) }}s
                                            </span>
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            {{ $i === 0 ? '1.º lugar' : ($i === 1 ? '2.º lugar' : '3.º lugar') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-center mt-2">
                            <a href="{{ route('winners', ['gameSession' => $gameSession->id]) }}"
                               class="btn btn-primary btn-sm" target="_blank">
                                Ver ranking completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div> {{-- /container-fluid --}}
    @elseif (!$gameSession->is_running || !$current || !$q)
        {{-- MODO ESPERA (no iniciado) --}}
        <div class="text-center w-100">
            <span class="badge badge-secondary mb-2 q-index small text-uppercase">
                Q #{{ min($gameSession->current_q_index + 1, $gameSession->questions_total) }} /
                {{ $gameSession->questions_total }}
            </span>
            <div class="q-title mb-1">Esperando que el docente inicie…</div>
            <div class="muted">Mantente listo. El juego comenzará en breve.</div>
        </div>
    @else
        {{-- PREGUNTA EN CURSO (con cronómetro y/o pausa para reveal) --}}
        <div class="container-fluid position-relative">

            {{-- Mostrar TIMER y ANUNCIADO solo mientras está corriendo (no en pausa) --}}
            @if ($gameSession->is_running && !$gameSession->is_paused)
                {{-- Cronómetro arriba a la derecha --}}
                <div class="position-absolute" style="top:12px; right:12px;">
                    <div class="timer-chip d-inline-flex align-items-center">
                        <span class="timer-icon mr-2"></span>
                        <span id="timerValue" class="timer-text">--:--</span>
                    </div>
                </div>

                {{-- Anunciado --}}
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-10 col-xl-8 text-center mb-4">
                        <div class="q-title">
                            <div class="ck-content">{!! $q->statement !!}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Al PAUSAR: ocultar anunciado y timer. Mostrar opciones (con correcta), resultados y retro. --}}
            @if ($gameSession->is_paused)
                {{-- Alternativas en 2 columnas con la correcta resaltada --}}
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="row">
                            @foreach ($q->options as $opt)
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="opt h-100 d-flex align-items-start justify-content-between {{ $opt->is_correct ? 'opt-correct' : '' }}">
                                        <div class="d-flex">
                                            <span class="opt-badge mr-3 text-white">{{ $opt->label }}</span>
                                            <span class="opt-text">{{ $opt->content }}</span>
                                        </div>
                                        @if ($opt->is_correct)
                                            <span class="badge badge-success badge-lg align-self-center">Correcta</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @php
                    // Totales y distribución por opción
                    $total = \App\Models\Answer::where('session_question_id', $current->id)
                        ->whereNotNull('question_option_id')
                        ->count();

                    $byOpt = \App\Models\Answer::selectRaw('question_option_id, COUNT(*) as c')
                        ->where('session_question_id', $current->id)
                        ->whereNotNull('question_option_id')
                        ->groupBy('question_option_id')
                        ->pluck('c', 'question_option_id');

                    // Campo de retroalimentación disponible (si hubiera)
                    $feedback = $q->feedback_html ?? ($q->feedback ?? ($q->explanation ?? null));
                @endphp

                {{-- Resultados --}}
                <div class="row justify-content-center mt-3">
                    <div class="col-12 col-xl-10">
                        <div class="results-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="mb-0">Resultados</h5>
                                <span class="badge badge-info badge-lg">
                                    Total respuestas: {{ $total }}
                                </span>
                            </div>

                            @if ($total === 0)
                                <div class="text-muted">Sin respuestas registradas aún.</div>
                            @else
                                @foreach ($q->options as $opt)
                                    @php
                                        $count = (int) $byOpt->get($opt->id, 0);
                                        $pct   = $total ? round(($count * 100) / $total) : 0;
                                        $barClass = $opt->is_correct ? 'bg-success' : 'bg-secondary';
                                    @endphp

                                    <div class="result-row">
                                        <div class="d-flex justify-content-between mb-1">
                                            <div class="d-flex align-items-center">
                                                <span class="opt-badge mr-2 text-white">{{ $opt->label }}</span>
                                                <span class="opt-text">{{ $opt->content }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge {{ $opt->is_correct ? 'badge-success' : 'badge-light' }} badge-lg">
                                                    {{ $pct }}% ({{ $count }})
                                                </span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar {{ $barClass }}" role="progressbar"
                                                style="width: {{ $pct }}%;"
                                                aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Retroalimentación (si existe) --}}
                @if ($feedback)
                    <div class="row justify-content-center mt-3">
                        <div class="col-12 col-xl-10">
                            <div class="retro-card p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-success mr-2">Retroalimentación</span>
                                    <strong class="mb-0">¿Por qué esta es la respuesta?</strong>
                                </div>
                                <div class="ck-content">{!! $feedback !!}</div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

        </div>
    @endif
</div>

@push('js')
<script>
(function () {
    const sid = @json($gameSession->id);
    const subKey = 'screen-' + sid;
    // Set global (no se pierde en re-renders)
    window.__screenWarnedKeys ??= new Set();
    window.__screenRevealedKeys ??= new Set();
    // Flag global para saber si ya desbloqueamos el audio por gesto
    window.__audioUnlocked ??= false;
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
        let lastPaused = null;

        function fmt(sec) {
            sec = Math.max(0, Math.floor(sec));
            const m = Math.floor(sec / 60).toString().padStart(2, '0');
            const s = (sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }

        function tick()
        {
            const root = document.getElementById('screen-root');
            if (!root) return; // ← solo chequea root
            const out  = document.getElementById('timerValue');

            const finished = root.dataset.finished === '1';
            if (finished) {
                if (out) out.textContent = '--:--';
                return;
            }

            const running = root.dataset.running === '1';
            const paused  = root.dataset.paused  === '1';
            const dur     = parseInt(root.dataset.duration || '0', 10);
            const idxStr  = root.dataset.index || '';
            const started = root.dataset.started;

            // si cambió la pregunta, resetea cache
            if (lastIndex !== idxStr) {
                lastIndex  = idxStr;
                lastLeft   = null;
                lastPaused = null; // para detectar la transición a pausa y tocar el ding
            }

            // --- DING al entrar en PAUSA (reveal) una sola vez por pregunta ---
            try {
                const key = (idxStr || '') + '|' + (started || '');
                if (lastPaused !== null && lastPaused === false && paused === true) {
                    if (!window.__screenRevealedKeys.has(key)) {
                        const ding = document.getElementById('audio-reveal-ding');
                        if (ding) {
                            if (ding.paused && ding.readyState < 2) ding.load();
                            ding.currentTime = 0;
                            const p = ding.play();
                            if (p && typeof p.catch === 'function') p.catch(()=>{});
                        }
                        window.__screenRevealedKeys.add(key);
                    }
                }
                lastPaused = paused;
            } catch (_) {}

            // ---- Calcula 'left' correctamente antes de usarlo
            let left = dur;
            if (!running || !started) {
                left = dur;
            } else if (paused) {
                if (lastLeft === null) {
                    const t0 = Date.parse(started);
                    const elapsed = Math.max(0, (Date.now() - t0) / 1000);
                    left = Math.max(0, Math.round(dur - elapsed));
                    lastLeft = left;
                } else {
                    left = lastLeft;
                }
            } else {
                const t0 = Date.parse(started);
                const elapsed = Math.max(0, (Date.now() - t0) / 1000);
                left = Math.max(0, Math.round(dur - elapsed));
                lastLeft = left;
            }

            // === Aviso sonoro cuando queden 3s ===
            try {
                const willPlayBeep =
                    running && !paused && started &&
                    left > 0 && left <= 3;

                if (willPlayBeep) {
                    const key = (idxStr || '') + '|' + (started || '');
                    if (!window.__screenWarnedKeys.has(key)) {
                        const beep = document.getElementById('audio-warning-3s');
                        if (beep) {
                            if (beep.paused && beep.readyState < 2) beep.load();
                            beep.currentTime = 0;
                            const playPromise = beep.play();
                            if (playPromise && typeof playPromise.catch === 'function') {
                                playPromise.catch(()=>{});
                            }
                        }
                        window.__screenWarnedKeys.add(key);
                    }
                }
            } catch (_) {}

            // Pinta el tiempo sólo si existe el nodo
            if (out) out.textContent = fmt(left);
        }

        window.__screenTimers[sid] = setInterval(tick, 500);
        tick();
    })();

    (function unlockAudioOnce()
    {
        if (window.__audioUnlocked) return;

        const tryUnlock = () => {
            const beep = document.getElementById('audio-warning-3s');
            const ding = document.getElementById('audio-reveal-ding');
            if (!beep && !ding) return;

            // Función auxiliar para “primar” un <audio>
            const prime = async (el) => {
                if (!el) return;
                try {
                    el.muted = true;
                    await el.play();
                    el.pause();
                    el.currentTime = 0;
                    el.muted = false;
                } catch (_) {}
            };

            Promise.resolve()
                .then(() => prime(beep))
                .then(() => prime(ding))
                .finally(() => {
                    window.__audioUnlocked = true;
                    window.removeEventListener('pointerdown', tryUnlock);
                    window.removeEventListener('touchstart', tryUnlock);
                    window.removeEventListener('click', tryUnlock);
                });
        };

        // Cualquier gesto del usuario
        window.addEventListener('pointerdown', tryUnlock, { once: true, passive: true });
        window.addEventListener('touchstart', tryUnlock, { once: true, passive: true });
        window.addEventListener('click', tryUnlock, { once: true, passive: true });
    })();

    // ===== Suscripción WS y refresco de estado =====
    function bootWs() {
        const ok   = !!(window.Livewire && window.Echo);
        const root = document.querySelector('#screen-root');
        let compId = root && root.getAttribute('wire:id');
        // fallback por si Livewire montó el id en otro nodo
        if (!compId) {
            const anyRoot = document.querySelector('[wire\\:id]');
            compId = anyRoot && anyRoot.getAttribute('wire:id');
        }

        if (!ok || !compId) return setTimeout(bootWs, 100);

        const call = (method) => {
            const comp = Livewire.find(compId);
            if (comp && typeof comp.call === 'function') comp.call(method);
        };

        try {
            const chan = window.Echo.private(`session.${sid}`);
            chan
                .listen('.GameSessionStateChanged', () => call('syncState'))
                .listen('.AnswerSubmitted',        () => call('syncState'))
                .listen('.GameCountdownStarted',   (e) => {
                    const secs  = parseInt(e?.seconds || 3, 10);
                    const phase = e?.phase || 'start'; // 'start' | 'advance'
                    showScreenCountdown(isNaN(secs) ? 3 : secs, phase);
                });
        } catch (e) {
            window.__panelSubs[subKey] = false;
            return setTimeout(bootWs, 400);
        }
    }
    bootWs();

    // ===== Overlay 3-2-1 en pantalla =====
    function showScreenCountdown(seconds, phase = 'start') {
        const wrap  = document.getElementById('screen-countdown');
        const num   = document.getElementById('screen-countdown-number');
        const title = document.getElementById('screen-countdown-title');
        if (!wrap || !num) return;

        if (title) {
            title.textContent = (phase === 'advance') ? '¡Siguiente pregunta!' : '¡Comenzamos!';
        }

        let s = Math.max(1, seconds | 0);
        num.textContent = s;
        wrap.classList.remove('d-none');

        const t1 = setTimeout(() => { num.textContent = Math.max(1, s - 1); }, 700);
        const t2 = setTimeout(() => { num.textContent = Math.max(1, s - 2); }, 1400);
        const t3 = setTimeout(() => {
            wrap.classList.add('d-none');
            clearTimeout(t1); clearTimeout(t2);
        }, 2100);
    }
})();
</script>
@endpush
