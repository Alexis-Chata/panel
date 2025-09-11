{{-- Raíz del componente (Livewire añadirá wire:id) --}}
<div id="screen-root" class="screen d-flex align-items-center justify-content-center p-4">
    @php $q = $current?->question; @endphp

    @if (!$gameSession->is_running || !$current || !$q)
        <div class="text-center w-100">
            <div class="q-index mb-2 small text-uppercase">
                Q #{{ $gameSession->current_q_index + 1 }} / {{ $gameSession->questions_total }}
            </div>
            <div class="q-title mb-1">Esperando que el docente inicie…</div>
            <div class="muted">Mantente listo. El juego comenzará en breve.</div>
        </div>
    @else
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 text-center mb-4">
                    <div class="q-title">{{ $q->statement }}</div>
                </div>

                <div class="col-12">
                    {{-- Opciones (solo visual) --}}
                    @foreach ($q->options as $opt)
                        <div
                            class="opt d-flex align-items-start justify-content-between {{ $gameSession->is_paused && $opt->is_correct ? 'opt-correct' : '' }}">
                            <div class="d-flex">
                                <span class="opt-badge mr-3 text-white">{{ $opt->label }}</span>
                                <span class="opt-text">{{ $opt->content }}</span>
                            </div>
                            @if ($gameSession->is_paused && $opt->is_correct)
                                <span class="badge badge-success badge-lg align-self-center">Correcta</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Suscripción WS sin wire:poll, sin x-init, sin getListeners --}}
@push('js')
    <script>
        (function() {
            const sid = @json($gameSession->id);
            const key = 'screen-' + sid;

            // evitar dobles suscripciones
            window.__panelSubs ??= {};
            if (window.__panelSubs[key]) return;
            window.__panelSubs[key] = true;

            function boot() {
                const ok = !!(window.Livewire && window.Echo);
                const root = document.querySelector('#screen-root');
                const compId = root && root.getAttribute('wire:id');
                if (!ok || !compId) return setTimeout(boot, 100);

                const call = (method) => {
                    const comp = Livewire.find(compId);
                    if (comp && typeof comp.call === 'function') comp.call(method);
                };

                try {
                    window.Echo.private(`session.${sid}`)
                        .listen('.GameSessionStateChanged', () => call('syncState'))
                        .listen('.AnswerSubmitted', () => call('syncState'));
                } catch (e) {
                    window.__panelSubs[key] = false;
                    setTimeout(boot, 400);
                }
            }
            boot();
        })();
    </script>
@endpush
