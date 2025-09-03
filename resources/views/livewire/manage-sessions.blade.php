<div>
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Nueva Partida</div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Título</label>
                    <input type="text" class="form-control" wire:model.defer="title" placeholder="Ej. Panel Básico">
                    @error('title')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>N° preguntas</label>
                    <input type="number" class="form-control" wire:model.defer="questions_total" min="1"
                        max="50">
                    @error('questions_total')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Tiempo por pregunta (s)</label>
                    <input type="number" class="form-control" wire:model.defer="timer_default" min="5"
                        max="600">
                    @error('timer_default')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Vista estudiante</label>
                    <select class="form-control" wire:model.defer="student_view_mode">
                        <option value="full">Enunciado + alternativas</option>
                        <option value="choices_only">Solo alternativas</option>
                    </select>
                    @error('student_view_mode')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <button class="btn btn-primary" wire:click="createSession">
                <i class="fas fa-plus mr-1"></i> Crear
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Partidas recientes</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Título</th>
                        <th>Activa</th>
                        <th>En curso</th>
                        <th>Preguntas</th>
                        <th>Vista</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $s)
                        <tr>
                            <td><span class="badge badge-secondary">{{ $s->code }}</span></td>
                            <td>{{ $s->title }}</td>
                            <td>{!! $s->is_active ? '<span class="badge badge-success">Sí</span>' : 'No' !!}</td>
                            <td>{!! $s->is_running ? '<span class="badge badge-info">Sí</span>' : 'No' !!}</td>
                            <td>{{ $s->questions_total }}</td>
                            <td>{{ $s->student_view_mode }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm {{ $s->is_active ? 'btn-warning' : 'btn-outline-success' }}"
                                    wire:click="toggleActive({{ $s->id }})">
                                    {{ $s->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button class="btn btn-sm btn-primary" wire:click="run({{ $s->id }})">
                                    Ejecutar
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="endSession({{ $s->id }})">
                                    Cerrar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
