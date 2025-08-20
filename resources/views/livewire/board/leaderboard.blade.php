<div class="container py-4"> {{-- ÚNICO ROOT --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
        <div>
            <h3 class="mb-1">{{ $session->title }}</h3>
            <div class="small text-muted">
                Código: <code>{{ $session->code }}</code>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-info text-dark">{{ $session->status }}</span>
            <div class="small text-muted">Fase actual: {{ $session->current_phase }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Top {{ $limit }}</div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:70px">#</th>
                        <th>Jugador</th>
                        <th style="width:150px" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->top as $i => $p)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $i + 1 }}</span></td>
                            <td>{{ $p->nickname ?? ($p->user->name ?? 'Jugador ' . $p->id) }}</td>
                            <td class="text-end fw-bold">{{ $p->total_score }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted p-3">Aún no hay participantes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Suscripción realtime (usa los mismos eventos) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = @json($session->id);

            // Nota: si esta pizarra es pública sin auth, deberías emitir a un canal público
            // y aquí suscribirte con Echo.channel(...). Con canales privados necesitas auth.

            window.Echo?.private(`sessions.${sessionId}.scores`)
                .listen('.ScoreUpdated', (e) => window.Livewire?.dispatch('score-updated', e));

            window.Echo?.private(`sessions.${sessionId}.phase`)
                .listen('.SessionPhaseChanged', (e) => window.Livewire?.dispatch('phase-changed'));
        });
    </script>
</div>
