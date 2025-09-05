<div class="card">
    @role('Admin|Docente')
        <div class="m-3">
            <a href="{{ route('sessions.export.excel', $gameSession) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel mr-1"></i> Resultados (Excel)
            </a>
            <a href="{{ route('sessions.export.pdf', $gameSession) }}" class="btn btn-outline-danger btn-sm ml-2">
                <i class="fas fa-file-pdf mr-1"></i> Resultados (PDF)
            </a>
            <a href="{{ route('sessions.export.analytics.excel', $gameSession) }}" class="btn btn-success btn-sm ml-2">
                <i class="fas fa-chart-bar mr-1"></i> Analítico (Excel)
            </a>
            <a href="{{ route('sessions.export.analytics.pdf', $gameSession) }}" class="btn btn-danger btn-sm ml-2">
                <i class="fas fa-chart-pie mr-1"></i> Analítico (PDF)
            </a>
        </div>
    @endrole

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
