<div class="container">
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    <div class="row mb-2">
        <div class="col-12">
            <button class="btn btn-primary"
                    data-toggle="modal" data-target="#gameSessionModal"
                    wire:click="nuevo">
                <i class="fas fa-plus mr-1"></i> Crear
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-dark">Partidas recientes</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover table-bordered mb-0 align-middle">
                <thead class="thead-light">
                <tr class="text-uppercase small">
                    <th style="width:100px;">Code</th>
                    <th>Título</th>
                    <th class="text-center" style="width:90px;">Activa</th>
                    <th class="text-center" style="width:100px;">En curso</th>
                    <th class="text-center" style="width:110px;">Preguntas</th>
                    <th class="text-center" style="width:160px;">Vista</th>
                    <th class="text-nowrap" style="width:260px;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($sessions as $s)
                <tr>
                    <td>
                    <span class="badge badge-secondary">{{ $s->code }}</span>
                    </td>
                    <td>{{ $s->title }}</td>
                    <td class="text-center">
                    {!! $s->is_active ? '<span class="badge badge-success">Sí</span>' : '<span class="text-muted">No</span>' !!}
                    </td>
                    <td class="text-center">
                    {!! $s->is_running ? '<span class="badge badge-info">Sí</span>' : '<span class="text-muted">No</span>' !!}
                    </td>
                    <td class="text-center">{{ $s->questions_total }}</td>

                    {{-- Columna Vista (span clickeable con confirm) --}}
                    @php
                    $isFull = $s->student_view_mode === 'completo';
                    $currentLabel = $isFull ? 'Completo' : 'Solo alternativas';
                    $nextLabel = $isFull ? 'Solo alternativas' : 'Completo';
                    $badgeClass = $isFull ? 'badge-primary' : 'badge-secondary';
                    @endphp
                    <td class="text-center">
                    <span role="button"
                            class="badge {{ $badgeClass }} px-2 py-1"
                            title="Cambiar a {{ $nextLabel }}"
                            wire:click="toggleStudentViewMode({{ $s->id }})"
                            wire:confirm="¿Cambiar vista a {{ $nextLabel }}?">
                        {{ $currentLabel }}
                    </span>
                    </td>


                    <td class="text-nowrap">
                    <button class="btn btn-sm {{ $s->is_active ? 'btn-warning' : 'btn-outline-success' }}"
                            wire:click="toggleActive({{ $s->id }})">
                        {{ $s->is_active ? 'Desactivar' : 'Activar' }}
                    </button>

                    <button class="btn btn-sm btn-primary" wire:click="run({{ $s->id }})">
                        Ejecutar
                    </button>

                    <button class="btn btn-sm btn-outline-danger"
                            wire:click="endSession({{ $s->id }})"
                            wire:confirm="¿Cerrar la partida {{ $s->code }}?">
                        Cerrar
                    </button>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer  bg-secondary py-2">
            {{ $sessions->links() }}
        </div>
    </div>

    {{-- Modal separado en include para orden --}}
    @include('admin.partida.game-session-modal')
</div>
