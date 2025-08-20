<div class="container py-3"> {{-- ÚNICO ROOT --}}
    {{-- Crear partida --}}
    <div class="card mb-3">
        <div class="card-header">Crear partida</div>
        <div class="card-body row g-2 align-items-center">
            <div class="col-md-8">
                <input class="form-control" placeholder="Título de la partida" wire:model.defer="title">
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-success" wire:click="create">Crear</button>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-header">Filtros</div>
        <div class="card-body row g-2">
            <div class="col-md-6">
                <input class="form-control" placeholder="Buscar por código o título" wire:model.live.debounce.400ms="q">
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="status">
                    <option value="">Todos los estados</option>
                    <option value="draft">draft</option>
                    <option value="lobby">lobby</option>
                    <option value="phase1">phase1</option>
                    <option value="phase2">phase2</option>
                    <option value="phase3">phase3</option>
                    <option value="results">results</option>
                    <option value="finished">finished</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="perPage">
                    <option value="10">10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="card-header">Partidas</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Fase</th>
                        <th>Jugadores</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>
                                <code class="me-2">{{ $s->code }}</code>
                                <button class="btn btn-outline-secondary btn-xs" data-copy="{{ $s->code }}"
                                    title="Copiar código">Copiar</button>
                            </td>
                            <td>{{ $s->title }}</td>
                            <td><span class="badge bg-info text-dark">{{ $s->status }}</span></td>
                            <td>{{ $s->current_phase }}</td>
                            <td>{{ $s->participants_count }}</td>
                            <td class="text-end">
                                <a class="btn btn-primary btn-sm"
                                    href="{{ route('admin.sessions.lobby', $s) }}">Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted p-3">No hay partidas para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sessions->hasPages())
            <div class="card-footer">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    {{-- JS para copiar código (opcional SweetAlert si lo tienes cargado) --}}
    <script>
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;
            const code = btn.getAttribute('data-copy');
            navigator.clipboard?.writeText(code).then(() => {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Copiado',
                        text: code,
                        icon: 'success',
                        timer: 900,
                        showConfirmButton: false
                    });
                } else {
                    alert('Copiado: ' + code);
                }
            });
        }, {
            passive: true
        });
    </script>
</div>
