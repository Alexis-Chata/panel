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
        {{-- 3) La partida está corriendo: pregunta + cronómetro sincronizado con servidor --}}
    @else
        <div {{-- Remonta Alpine al cambiar de pregunta o de marca de inicio --}}
            wire:key="q-{{ $gameSession->id }}-{{ $gameSession->current_q_index }}-{{ optional($gameSession->current_q_started_at)->timestamp ?? 'none' }}"
            {{-- Datos que Livewire actualiza cada render --}} data-left="{{ $secondsLeft }}"
            data-running="{{ $gameSession->is_running ? 'true' : 'false' }}"
            data-paused="{{ $gameSession->is_paused ? 'true' : 'false' }}" x-data="{
                seconds: parseInt($el.getAttribute('data-left') || '0', 10),
                running: {{ !$gameSession->is_paused && $gameSession->is_running ? 'true' : 'false' }},
                answered: {{ $answered_option_id ? 'true' : 'false' }},

                tick() {
                    if (!this.running || this.answered) return;
                    if (this.seconds > 0) {
                        this.seconds--;
                        if (this.seconds <= 0 && !this.answered) {
                            // tiempo agotado; el servidor validará nuevamente
                            $wire.answer(null, 0);
                        }
                    }
                },

                syncFromServer() {
                    const left = parseInt($el.getAttribute('data-left') || '0', 10);
                    const srvRun = $el.getAttribute('data-running') === 'true';
                    const srvPause = $el.getAttribute('data-paused') === 'true';
                    this.running = srvRun && !srvPause;

                    // Solo AJUSTA hacia abajo (nunca sube)
                    if (!Number.isNaN(left) && left < this.seconds) {
                        this.seconds = left;
                    }
                    // Si por alguna razón seconds no está inicializado, ponlo
                    if (Number.isNaN(this.seconds)) this.seconds = left;
                }
            }"
            x-init="// sincroniza al montar
            syncFromServer();

            // loop 1s
            const __int = setInterval(() => { recompute() }, 1000);

            // si deja de correr, corta (evita seguir llamando $wire)
            $watch('running', v => { if (!v) clearInterval(__int) });

            // cleanup cuando Livewire reemplace este nodo
            return () => { clearInterval(__int) };" x-effect="syncFromServer()" class="card">
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
                            :disabled="answered || !running"
                            @click="
            if (!answered && running) {
              answered = true;
              $wire.answer({{ $opt->id }}, 0); // el server registra el tiempo real
            }
          ">
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
