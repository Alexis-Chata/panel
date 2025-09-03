<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Top 10</h5>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Participante</th>
                        <th>Puntaje</th>
                        <th>Tiempo total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p->nickname ?? $p->user?->name }}</td>
                            <td><span class="badge badge-success">{{ $p->score }}</span></td>
                            <td>{{ number_format($p->time_total_ms / 1000, 2) }} s</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Sin participantes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('panel') }}" class="btn btn-outline-secondary btn-sm mt-2">Volver</a>
    </div>
</div>
