<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-2">Bienvenido</h5>
                <p class="mb-3">Usa el menú para gestionar partidas o unirte.</p>

                <a href="{{ route('join') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-sign-in-alt mr-1"></i> Unirme a una partida
                </a>

                @role('Admin|Docente')
                    <a href="{{ route('sessions.index') }}" class="btn btn-secondary btn-sm ml-2">
                        <i class="fas fa-gamepad mr-1"></i> Gestionar Partidas
                    </a>
                @endrole

                @if ($active)
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-bullhorn mr-1"></i>
                        Hay una partida activa: <strong>{{ $active->title ?? $active->code }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
