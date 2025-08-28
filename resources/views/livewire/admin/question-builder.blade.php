<div class="container py-3">
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="row">
        {{-- ===== Columna izquierda: POOLS ===== --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Question Pools</span>
                    <button class="btn btn-success btn-sm" wire:click="createPool">Nuevo</button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($pools as $p)
                        <button
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedPoolId === $p->id ? 'active' : '' }}"
                            wire:click="selectPool({{ $p->id }})">
                            <div>
                                <div class="font-weight-bold">{{ $p->name }}</div>
                                <small>{{ $p->slug }} @if ($p->intended_phase)
                                        · Fase {{ $p->intended_phase }}
                                    @endif
                                </small>
                            </div>
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    @empty
                        <div class="list-group-item text-muted">Sin pools.</div>
                    @endforelse
                </div>
            </div>

            {{-- Editor Pool --}}
            <div class="card">
                <div class="card-header">Editor de Pool</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" wire:model.defer="poolForm.name">
                        @error('poolForm.name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" class="form-control" wire:model.defer="poolForm.slug"
                            placeholder="se-generará-si-lo-dejas-vacío">
                        @error('poolForm.slug')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Fase prevista</label>
                        <select class="form-control" wire:model.defer="poolForm.intended_phase">
                            <option value="">—</option>
                            <option value="1">Fase 1</option>
                            <option value="2">Fase 2</option>
                            <option value="3">Fase 3</option>
                        </select>
                        @error('poolForm.intended_phase')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-danger" wire:click="deletePool({{ $poolForm->id ?? 0 }})"
                        @if (!$poolForm->id) disabled @endif>Eliminar</button>
                    <div>
                        <button class="btn btn-outline-secondary" wire:click="createPool">Limpiar</button>
                        <button class="btn btn-primary" wire:click="savePool">Guardar Pool</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Columna derecha: QUESTIONS ===== --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap align-items-center">
                    <div class="mr-auto">
                        Preguntas
                        @if ($selectedPoolId)
                            <small class="text-muted">· Pool #{{ $selectedPoolId }}</small>
                        @endif
                    </div>
                    <div class="form-inline">
                        <input class="form-control form-control-sm mr-2" placeholder="Buscar stem o código"
                            wire:model.live.debounce.350ms="q">
                        <select class="form-control form-control-sm" wire:model="perPage">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <button class="btn btn-success btn-sm ml-2" wire:click="createQuestion"
                        @if (!$selectedPoolId) disabled @endif>Nueva pregunta</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Stem</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($questions as $q)
                                <tr>
                                    <td>{{ $q->id }}</td>
                                    <td><code>{{ $q->code }}</code></td>
                                    <td>{{ $q->type }}</td>
                                    <td class="text-truncate" style="max-width: 420px;">{{ $q->stem }}</td>
                                    <td class="text-right">
                                        <button class="btn btn-primary btn-sm"
                                            wire:click="editQuestion({{ $q->id }})">Editar</button>
                                        <button class="btn btn-danger btn-sm"
                                            wire:click="deleteQuestion({{ $q->id }})">Eliminar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted p-3">No hay preguntas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($questions->hasPages())
                    <div class="card-footer">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>

            {{-- Editor de Pregunta --}}
            <div class="card">
                <div class="card-header">
                    Editor de Pregunta
                    @if ($editingQuestionId)
                        <small class="text-muted">#{{ $editingQuestionId }}</small>
                    @endif
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tipo</label>
                            <select class="form-control" wire:model.defer="questionForm.type">
                                <option value="multiple_choice">multiple_choice</option>
                                <option value="true_false">true_false</option>
                                <option value="short_answer">short_answer</option>
                            </select>
                            @error('questionForm.type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label>Código</label>
                            <input type="text" class="form-control" wire:model.defer="questionForm.code"
                                placeholder="auto si vacío">
                            @error('questionForm.code')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label>Dificultad</label>
                            <input type="number" min="1" max="5" class="form-control"
                                wire:model.defer="questionForm.difficulty">
                            @error('questionForm.difficulty')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tiempo (seg)</label>
                            <input type="number" min="5" max="600" class="form-control"
                                wire:model.defer="questionForm.time_limit_seconds">
                            @error('questionForm.time_limit_seconds')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Enunciado (stem)</label>
                        <textarea class="form-control" rows="3" wire:model.defer="questionForm.stem"></textarea>
                        @error('questionForm.stem')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Opciones (solo para multiple_choice y true_false) --}}
                    @if (in_array($questionForm->type, ['multiple_choice', 'true_false']))
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Opciones</h6>
                            <button class="btn btn-outline-primary btn-sm" wire:click="addOptionRow"
                                @if ($questionForm->type === 'true_false') disabled @endif>Agregar opción</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th style="width:10%">Etiqueta</th>
                                        <th>Valor</th>
                                        <th style="width:15%">Correcta</th>
                                        <th class="text-right" style="width:12%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($questionForm->options as $i => $opt)
                                        <tr>
                                            <td>
                                                <input type="text" class="form-control"
                                                    wire:model.defer="questionForm.options.{{ $i }}.label"
                                                    @if ($questionForm->type === 'true_false') readonly @endif>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control"
                                                    wire:model.defer="questionForm.options.{{ $i }}.value">
                                            </td>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="optc{{ $i }}"
                                                        wire:model.defer="questionForm.options.{{ $i }}.is_correct">
                                                    <label class="custom-control-label"
                                                        for="optc{{ $i }}">Correcta</label>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <button class="btn btn-outline-danger btn-sm"
                                                    wire:click="removeOptionRow({{ $i }})"
                                                    @if ($questionForm->type === 'true_false') disabled @endif>Quitar</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted">Sin opciones.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-danger" wire:click="deleteQuestion({{ $editingQuestionId ?? 0 }})"
                        @if (!$editingQuestionId) disabled @endif>Eliminar</button>
                    <div>
                        <button class="btn btn-outline-secondary" wire:click="createQuestion"
                            @if (!$selectedPoolId) disabled @endif>Limpiar</button>
                        <button class="btn btn-primary" wire:click="saveQuestion"
                            @if (!$selectedPoolId) disabled @endif>Guardar Pregunta</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
