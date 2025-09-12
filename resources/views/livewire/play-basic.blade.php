@php
    $timer = $current ? $current->timer_override ?? $gameSession->timer_default : 0;
@endphp

<div>
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
                                    <img src="{{ $p['photo_url'] }}" class="avatar-photo" alt="{{ $p['name'] }}" loading="lazy">
                                    <span class="presence-dot" aria-hidden="true"></span> {{-- se vuelve “verde” si usas presence --}}
                                </div>
                                <div class="avatar-name text-truncate small">{{ $p['first_name'] }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">Aún no hay participantes conectados.</div>
                        @endforelse
                    </div>

                    <div class="mt-3 small text-muted">
                        {{ count($roster) }} participante{{ count($roster) === 1 ? '' : 's' }} conectado{{ count($roster) === 1 ? '' : 's' }}
                    </div>
                </div>
            </div>
        {{-- 3) La partida está corriendo: pregunta + cronómetro sincronizado con servidor --}}
        @else
        <div
            wire:key="q-{{ $gameSession->id }}-{{ $gameSession->current_q_index }}-{{ optional($gameSession->current_q_started_at)->timestamp ?? 'none' }}"
            data-left="{{ $secondsLeft }}"
            data-running="{{ $gameSession->is_running ? 'true' : 'false' }}"
            data-paused="{{ $gameSession->is_paused ? 'true' : 'false' }}"
            x-data="{
                seconds: parseInt($el.getAttribute('data-left') || '0', 10),
                total: {{ (int) $timer }},
                running: {{ (!$gameSession->is_paused && $gameSession->is_running) ? 'true' : 'false' }},
                answered: {{ $answered_option_id ? 'true' : 'false' }},
                percent() {
                    if (!this.total) return 0;
                    const p = Math.round((this.seconds / this.total) * 100);
                    return Math.max(0, Math.min(100, p));
                },
                tick() {
                    if (!this.running || this.answered) return;
                    if (this.seconds > 0) {
                        this.seconds--;
                        if (this.seconds <= 0 && !this.answered) {
                            $wire.answer(null, 0);
                        }
                    }
                },
                syncFromServer() {
                    const left = parseInt($el.getAttribute('data-left') || '0', 10);
                    const srvRun = $el.getAttribute('data-running') === 'true';
                    const srvPause = $el.getAttribute('data-paused') === 'true';
                    this.running = srvRun && !srvPause;
                    if (!Number.isNaN(left) && left < this.seconds) this.seconds = left;
                    if (Number.isNaN(this.seconds)) this.seconds = left;
                },
                handleKey(e) {
                    if (!this.running || this.answered) return;
                    const map = {'1':0,'2':1,'3':2,'4':3,'5':4,'6':5,'7':6,'8':7,'9':8};
                    if (e.key in map) {
                        const idx = map[e.key];
                        const btn = $el.querySelectorAll('.option-card')[idx];
                        if (btn) btn.click();
                    }
                }
            }"
            x-init="
                syncFromServer();
                const __int = setInterval(() => { tick() }, 1000);   // ← FIX: tick()
                window.addEventListener('keydown', handleKey);
                $watch('running', v => { if (!v) clearInterval(__int) });
                return () => { clearInterval(__int); window.removeEventListener('keydown', handleKey); };
            "
            x-effect="syncFromServer()"
            class="card question-card card-lift card-lift--dark"
        >
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
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

                {{-- Barra de tiempo ligada a Alpine --}}
                <div class="progress timebar mb-3" aria-label="Tiempo restante">
                    <div class="progress-bar"
                        role="progressbar"
                        :style="`width: ${percent()}%`"
                        :aria-valuenow="percent()"
                        aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>

                @if ($gameSession->student_view_mode === 'full')
                    <h5 class="mb-3">{{ $current->question->statement }}</h5>
                @endif

                {{-- Alternativas como cards --}}
                <div class="options-wrap">
                    @foreach ($current->question->options->sortBy('opt_order') as $opt)
                        @php
                            $isSelected = $answered_option_id === $opt->id;
                            $isCorrect = (bool) $opt->is_correct;
                            $showCorrect = $gameSession->is_paused && $isCorrect;
                            $showWrong = $gameSession->is_paused && $isSelected && !$isCorrect;
                        @endphp

                        <button type="button"
                                class="option-card list-group-item list-group-item-action d-flex align-items-center
                                    {{ $isSelected ? 'selected' : '' }}
                                    {{ $showCorrect ? 'is-correct' : '' }}
                                    {{ $showWrong ? 'is-wrong' : '' }}"
                                :disabled="answered || !running"
                                @click="
                                    if (!answered && running) {
                                        answered = true;
                                        $wire.answer({{ $opt->id }}, 0);
                                    }
                                ">
                            <span class="option-bubble mr-3">{{ $opt->label }}</span>
                            <span class="option-text flex-fill text-left">{{ $opt->content }}</span>

                            {{-- Estado visual a la derecha --}}
                            @if ($showCorrect)
                                <span class="right-pill">
                                    <i class="fas fa-check"></i>
                                </span>
                            @elseif ($showWrong)
                                <span class="right-pill wrong">
                                    <i class="fas fa-times"></i>
                                </span>
                            @elseif ($isSelected)
                                <span class="right-pill neutral">
                                    <i class="fas fa-dot-circle"></i>
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Feedback y estados inferiores --}}
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

@push('js')
    <script>
        (function() {
            const sid = @json($gameSession->id); // id real de la sesión
            const key = 'play-auto-' + sid;

            // Evita doble suscripción entre navegaciones/livewire
            window.__panelSubs ??= {};
            if (window.__panelSubs[key]) return;
            window.__panelSubs[key] = true;

            // Espera a que Livewire, Echo y el componente estén listos
            function getCompId() {
                // Prioriza el root marcado
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
                        .listen('.AnswerSubmitted', () => callSafe('refreshStats'));
                } catch (e) {
                    console.warn('Suscripción WS falló, reintentando...', e);
                    window.__panelSubs[key] = false; // permite reintento
                    setTimeout(boot, 300);
                    return;
                }
            })();
        })();
    </script>
@endpush

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

            try {
                window.Echo.private(`session.${sid}`)
                    .listen('.GameSessionStateChanged', () => callSafe('syncState'))
                    .listen('.AnswerSubmitted',        () => callSafe('refreshStats'))
                    // ▼ Opcional si emites estos eventos al unirse/salir
                    .listen('.ParticipantJoined',      () => callSafe('refreshRoster'))
                    .listen('.ParticipantLeft',        () => callSafe('refreshRoster'));
            } catch (e) {
                console.warn('Suscripción WS falló, reintentando...', e);
                window.__panelSubs[key] = false;
                setTimeout(boot, 300);
                return;
            }
        })();
    })();
</script>
@endpush

