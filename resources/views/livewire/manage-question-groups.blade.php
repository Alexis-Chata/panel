<div x-data
    x-on:confirm-delete-group.window="
        if (confirm('¿Eliminar este grupo?')) { Livewire.dispatch('delete-group-now'); }
     ">

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('ok') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Filtros y acciones --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center">
            <div class="form-inline mr-3 mb-2">
                <label class="mr-2">Buscar</label>
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                    placeholder="nombre o descripción">
            </div>

            <div class="form-inline mr-3 mb-2">
                <label class="mr-2">Mostrar</label>
                <select class="form-control form-control-sm" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <button class="btn btn-primary btn-sm ml-auto mb-2" wire:click="openCreate">
                <i class="fas fa-plus mr-1"></i> Nuevo Grupo
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $g)
                        <tr>
                            <td>{{ $rows->firstItem() + $i }}</td>
                            <td>{{ $g->name }}</td>
                            <td style="max-width:400px;">
                                {{ Str::limit($g->description, 80) }}
                            </td>
                            <td>
                                @if ($g->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="openEdit({{ $g->id }})">
                                    Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="confirmDelete({{ $g->id }})">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Sin grupos registrados</td>
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
        @if ($showModal) style="display:block;background:rgba(0,0,0,.5)" @endif>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $group_id ? 'Editar' : 'Nuevo' }} Grupo
                    </h5>
                    <button type="button" class="close" aria-label="Close" wire:click="$set('showModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" wire:model.defer="name">
                        @error('name')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Descripción (opcional)</label>
                        <textarea class="form-control" rows="2" wire:model.defer="description"></textarea>
                        @error('description')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model.defer="is_active">
                        <label class="form-check-label" for="is_active">
                            Activo
                        </label>
                        @error('is_active')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showModal', false)">
                        Cerrar
                    </button>
                    <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
