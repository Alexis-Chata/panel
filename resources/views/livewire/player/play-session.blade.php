<div class="container py-3">
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-1">
                {{ $session->title }}
                <small class="text-muted">[{{ $session->status }}]</small>
            </h4>
            <div>Mi puntaje: <b>{{ $participant->total_score }}</b></div>
        </div>
    </div>

    @if (!$current)
        <div class="alert alert-info">No hay más preguntas por ahora. Espera instrucciones…</div>
    @else
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Pregunta #{{ $current->order }} (fase {{ $current->phase }})</span>

                @if ((int) $current->phase === 2)
                    <span class="badge bg-secondary">
                        Ronda {{ $phase2Round }} / {{ $phase2Total }}
                    </span>
                    <span class="ms-2">
                        @php
                            $me = $participant->nickname ?? $participant->user?->name;
                            $opp = $opponent?->nickname ?? ($opponent?->user?->name ?? 'BYE');
                        @endphp
                        ⚔️ {{ $me }} vs {{ $opp }}
                    </span>
                    <span class="ms-2">
                        (F2: {{ $participant->phase2_score }} pts
                        @if ($opponent)
                            — Rival: {{ $opponent->phase2_score }} pts
                        @endif)
                    </span>
                @endif

                <span class="timer-wrap" data-deadline="{{ $deadlineIso ?? '' }}" data-qid="{{ $current?->id ?? '' }}">
                    ⏳ <b class="js-timer" wire:ignore>—</b>s
                </span>
            </div>
            <div class="card-body">
                <p class="lead mb-3">{{ $current->question->stem }}</p>

                @php($type = $current->question->type)

                {{-- Enviamos TODO en un único submit --}}
                <form wire:submit.prevent="answer">
                    @if (in_array($type, ['single', 'boolean']))
                        @foreach ($current->question->options as $idx => $opt)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answer_option"
                                    id="opt{{ $opt->id }}" value="{{ $opt->id }}"
                                    wire:model.defer="selectedOptionId"
                                    @if ($idx === 0) required @endif>
                                <label class="form-check-label" for="opt{{ $opt->id }}">
                                    {{ $opt->label }}
                                </label>
                            </div>
                        @endforeach
                    @elseif(in_array($type, ['numeric', 'text']))
                        <div class="mb-2">
                            <input type="{{ $type === 'numeric' ? 'number' : 'text' }}" class="form-control"
                                placeholder="{{ $type === 'numeric' ? 'Ingresa un número' : 'Escribe tu respuesta' }}"
                                wire:model.defer="freeText" required>
                        </div>
                    @else
                        <div class="text-muted">Tipo de pregunta no soportado aún.</div>
                    @endif

                    <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled">
                        Enviar
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Countdown: usa expires_at (deadlineIso) calculado en servidor --}}
    <script>
        (() => {
            // Reinicia un único contador <b class="js-timer"> dentro de su .timer-wrap
            function bootTimer(wrap) {
                const b = wrap.querySelector('.js-timer');
                if (!b) return;

                // Limpia timer previo si existe
                if (b._timerId) {
                    clearInterval(b._timerId);
                    b._timerId = null;
                }

                const iso = wrap.getAttribute('data-deadline');
                if (!iso) {
                    b.textContent = '—';
                    return;
                }

                const deadline = Date.parse(iso);
                if (Number.isNaN(deadline)) {
                    b.textContent = '—';
                    return;
                }

                const compId = wrap.closest('[wire\\:id]')?.getAttribute('wire:id');

                const tick = () => {
                    const remain = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
                    b.textContent = remain;
                    if (remain === 0) {
                        clearInterval(b._timerId);
                        b._timerId = null;
                        const comp = compId && window.Livewire?.find(compId);
                        comp && comp.call('timeUp');
                    }
                };

                b._timerId = setInterval(tick, 250);
                tick();
            }

            // Escanea todos los contadores visibles
            function scan(root = document) {
                root.querySelectorAll('.timer-wrap').forEach(bootTimer);
            }

            // 1) Inicial inmediato
            scan();

            // 2) Observa cambios del DOM y del atributo data-deadline en cualquier .timer-wrap
            const observer = new MutationObserver((muts) => {
                for (const m of muts) {
                    if (m.type === 'childList') {
                        m.addedNodes.forEach((n) => {
                            if (n.nodeType !== 1) return;
                            if (n.matches?.('.timer-wrap')) bootTimer(n);
                            n.querySelectorAll?.('.timer-wrap').forEach(bootTimer);
                        });
                    }
                    if (m.type === 'attributes' && m.target.matches?.('.timer-wrap')) {
                        if (m.attributeName === 'data-deadline') bootTimer(m.target);
                    }
                }
            });

            observer.observe(document.documentElement, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['data-deadline']
            });
        })();
    </script>

    {{-- Suscripciones realtime --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = @json($session->id);

            // Canales privados
            window.Echo?.private(`sessions.${sessionId}.scores`)
                .listen('.ScoreUpdated', (e) => window.Livewire?.dispatch('score-updated', e));

            window.Echo?.private(`sessions.${sessionId}.phase`)
                .listen('.SessionPhaseChanged', () => window.Livewire?.dispatch('phase-changed'));

            // SweetAlert puente (desde $this->dispatch('swal', ...))
            window.addEventListener('swal', (evt) => {
                const {
                    title,
                    icon
                } = evt.detail || {};
                window.Swal?.fire({
                    title,
                    icon,
                    timer: 1000,
                    showConfirmButton: false
                });
            });
        });
    </script>
</div>
