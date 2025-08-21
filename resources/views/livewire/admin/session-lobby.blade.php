<div class="container py-3"> {{-- ÚNICO ROOT --}}
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ $session->title }} <small class="text-muted">[#{{ $session->id }}]</small></h4>
            <div class="small text-muted">
                Código: <code id="sess-code">{{ $session->code }}</code>
                <button class="btn btn-xs btn-outline-secondary" id="copy-code">Copiar</button>
            </div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-info text-dark align-self-center">{{ $session->status }}</span>
            <div class="btn-group">
                <button class="btn btn-outline-primary" wire:click="startPhase(1)">Fase 1</button>
                <button class="btn btn-outline-primary" wire:click="startPhase(2)">Fase 2</button>
                <button class="btn btn-outline-primary" wire:click="startPhase(3)">Fase 3</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-warning" wire:click="toResults">Resultados</button>
                <button class="btn btn-outline-danger" wire:click="finish">Finalizar</button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Participantes --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Participantes ({{ $session->participants->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($session->participants as $p)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $p->nickname ?? ($p->user->name ?? 'Jugador ' . $p->id) }}</span>
                            <span class="text-muted">Total: <b>{{ $p->total_score }}</b></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Ranking --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Ranking</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Jugador</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->ranking as $i => $p)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $p->nickname ?? ($p->user->name ?? 'Jugador ' . $p->id) }}</td>
                                    <td>{{ $p->total_score }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Encuentros (Fase 2) --}}
        @if ($this->matches->count())
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Encuentros 1v1 (fase 2)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Jugador 1</th>
                                    <th>Jugador 2</th>
                                    <th>Estado</th>
                                    <th>Ganador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->matches as $m)
                                    <tr>
                                        <td>{{ $m->id }}</td>
                                        <td>{{ $m->p1?->nickname ?? ($m->p1?->user?->name ?? '-') }}</td>
                                        <td>{{ $m->p2?->nickname ?? ($m->p2?->user?->name ?? '-') }}</td>
                                        <td><span class="badge bg-secondary">{{ $m->status }}</span></td>
                                        <td>{{ $m->winner?->nickname ?? ($m->winner?->user?->name ?? '-') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Subscripción realtime + copiar código --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = @json($session->id);

            window.Echo?.private(`sessions.${sessionId}.scores`)
                .listen('.ScoreUpdated', () => window.Livewire?.dispatch('score-updated'))
                .listen('.ParticipantUpdated', () => window.Livewire?.dispatch('participant-updated'));

            window.Echo?.private(`sessions.${sessionId}.phase`)
                .listen('.SessionPhaseChanged', () => window.Livewire?.dispatch('phase-changed'));

            document.getElementById('copy-code')?.addEventListener('click', () => {
                const code = document.getElementById('sess-code')?.textContent?.trim();
                if (!code) return;
                navigator.clipboard?.writeText(code).then(() => {
                    if (window.Swal) Swal.fire({
                        title: 'Copiado',
                        text: code,
                        icon: 'success',
                        timer: 900,
                        showConfirmButton: false
                    });
                });
            }, {
                passive: true
            });
        });
    </script>
</div>
