<div x-data
    x-on:confirm-delete.window="
        if (confirm('¿Eliminar esta pregunta?')) { Livewire.dispatch('delete-now'); }
    ">
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center">
            <div class="form-inline mr-3">
                <label class="mr-2">Buscar</label>
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                    placeholder="enunciado o feedback">
            </div>
            <div class="form-inline mr-3">
                <label class="mr-2">Mostrar</label>
                <select class="form-control form-control-sm" wire:model.live="perPage">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
            </div>
            <button class="btn btn-primary btn-sm ml-auto" wire:click="openCreate">
                <i class="fas fa-plus mr-1"></i> Nueva Pregunta
            </button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Importar preguntas (CSV/Excel)</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center">
                <a href="{{ route('questions.template.csv') }}" class="btn btn-outline-secondary btn-sm mr-2">
                    <i class="fas fa-download mr-1"></i> Descargar plantilla CSV
                </a>

                <div class="custom-file mr-2" style="max-width:340px;">
                    <input type="file" class="custom-file-input" id="fileImport" wire:model="file"
                        accept=".csv,.xlsx">
                    <label class="custom-file-label" for="fileImport">
                        {{ $file ? $file->getClientOriginalName() : 'Seleccionar archivo…' }}
                    </label>
                </div>

                <button class="btn btn-primary btn-sm" wire:click="import" wire:loading.attr="disabled">
                    <i class="fas fa-file-import mr-1"></i> Importar
                </button>
            </div>
            @error('file')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Enunciado</th>
                        <th>Feedback</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $q)
                        <tr>
                            <td>{{ $rows->firstItem() + $i }}</td>
                            <td style="max-width:600px">{{ Str::limit($q->statement, 120) }}</td>
                            <td style="max-width:400px">{{ Str::limit($q->feedback, 80) }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="openEdit({{ $q->id }})">Editar</button>
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="confirmDelete({{ $q->id }})">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Sin registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2">
            {{ $rows->links() }}
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <div class="modal fade @if ($showModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showModal) style="background:rgba(0,0,0,.5)" @endif>
        <div class="modal-dialog modal-lg" role="document" wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $q_id ? 'Editar' : 'Nueva' }} Pregunta</h5>
                    <button type="button" class="close" aria-label="Close" wire:click="$set('showModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Enunciado</label>
                        <textarea class="form-control" rows="3" wire:model.defer="statement"></textarea>
                        @error('statement')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Feedback (opcional)</label>
                        <textarea class="form-control" rows="2" wire:model.defer="feedback"></textarea>
                        @error('feedback')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        @foreach ($opts as $k => $o)
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header py-1 d-flex justify-content-between align-items-center">
                                        <span>Opción {{ $o['label'] }}</span>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model.defer="opts.{{ $k }}.is_correct"
                                                id="ok{{ $k }}">
                                            <label class="form-check-label"
                                                for="ok{{ $k }}">Correcta</label>
                                        </div>
                                    </div>
                                    <div class="card-body p-2">
                                        <input type="text" class="form-control form-control-sm"
                                            placeholder="Contenido"
                                            wire:model.defer="opts.{{ $k }}.content">
                                        @error('opts.' . $k . '.content')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                        <input type="hidden" wire:model="opts.{{ $k }}.label">
                                        <input type="hidden" wire:model="opts.{{ $k }}.opt_order">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('opts')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showModal', false)">Cerrar</button>
                    <button class="btn btn-primary" wire:click="save">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
