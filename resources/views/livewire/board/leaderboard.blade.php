<div class="container py-3">

    @php($BQ = $this->boardQuestion)

    @if ($BQ)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>
                    Pregunta @if ($BQ['phase'] === 3)
                        (Fase 3)
                    @else
                        (Fase 1)
                    @endif — #{{ $BQ['order'] }}
                </span>
                <span class="timer-wrap" data-deadline="{{ $BQ['expires'] ?? '' }}">
                    ⏳ <b class="js-timer" wire:ignore>—</b>s
                </span>
            </div>
            <div class="card-body">
                <p class="lead mb-0">{{ $BQ['stem'] }}</p>
            </div>
        </div>
    @endif

    {{-- TOP / Ranking --}}
    <div class="card">
        <div class="card-header">Top {{ $limit }}</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jugador</th>
                        <th>Puntaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->top as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p->nickname ?? $p->user?->name }}</td>
                            <td>{{ $p->total_score }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Timer robusto: reinicia al cambiar data-deadline o al re-render --}}
    <script>
        (() => {
            function bootTimer(wrap) {
                const b = wrap.querySelector('.js-timer');
                if (!b) return;

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

                const tick = () => {
                    const remain = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
                    b.textContent = remain;
                    if (remain === 0) {
                        clearInterval(b._timerId);
                        b._timerId = null;
                    }
                };

                b._timerId = setInterval(tick, 250);
                tick();
            }

            function scan(root = document) {
                root.querySelectorAll('.timer-wrap').forEach(bootTimer);
            }

            // inicial
            scan();

            // observa cambios de deadline y re-renders
            const mo = new MutationObserver((muts) => {
                for (const m of muts) {
                    if (m.type === 'attributes' && m.target.matches?.('.timer-wrap') && m.attributeName ===
                        'data-deadline') {
                        bootTimer(m.target);
                    }
                    if (m.type === 'childList' && m.addedNodes?.length) {
                        m.addedNodes.forEach(n => {
                            if (n.nodeType !== 1) return;
                            if (n.matches?.('.timer-wrap')) bootTimer(n);
                            n.querySelectorAll?.('.timer-wrap').forEach(bootTimer);
                        });
                    }
                }
            });
            mo.observe(document.documentElement, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['data-deadline']
            });
        })();
    </script>

    {{-- Suscripciones realtime: fase, puntajes y alta de participantes --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = @json($session->id);

            window.Echo?.private(`sessions.${sessionId}.scores`)
                .listen('.ScoreUpdated', () => window.Livewire?.dispatch('score-updated'));

            window.Echo?.private(`sessions.${sessionId}.phase`)
                .listen('.SessionPhaseChanged', () => window.Livewire?.dispatch('phase-changed'));

            window.Echo?.private(`sessions.${sessionId}.participants`)
                .listen('.ParticipantUpdated', () => window.Livewire?.dispatch('participant-updated'));
        });
    </script>

</div>
