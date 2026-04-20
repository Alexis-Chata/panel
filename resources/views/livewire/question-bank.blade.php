<div x-data @qb-confirm-delete-category.window="
        if (confirm('¿Eliminar esta categoría? Solo si está vacía.')) { Livewire.dispatch('qb-delete-category-now'); }
    " @qb-confirm-delete-question.window="
        if (confirm('¿Eliminar esta pregunta?')) { Livewire.dispatch('qb-delete-question-now'); }
    ">

    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            {{ session('ok') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button type="button"
                class="nav-link border-0 bg-transparent {{ $activeTab === 'categories' ? 'active' : '' }}"
                wire:click="$set('activeTab', 'categories')">
                <i class="fas fa-folder-tree mr-1"></i> Categorías
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link border-0 bg-transparent {{ $activeTab === 'questions' ? 'active' : '' }}"
                wire:click="$set('activeTab', 'questions')">
                <i class="fas fa-question-circle mr-1"></i> Preguntas
            </button>
        </li>
    </ul>

    {{-- ===== Pestaña categorías ===== --}}
    @if ($activeTab === 'categories')
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center">
                <div class="form-inline mr-3 mb-2">
                    <label class="mr-2">Buscar</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="categorySearch"
                        placeholder="nombre o descripción">
                </div>
                <button type="button" class="btn btn-primary btn-sm ml-auto mb-2" wire:click="openCategoryCreate(null)">
                    <i class="fas fa-folder-plus mr-1"></i> Nueva categoría
                </button>
            </div>
            <div class="card-footer py-2 small text-muted border-top-0 pt-0">
                <i class="fas fa-question-circle mr-1"></i>
                Total en tu banco (según tus permisos): <strong>{{ $totalAccessibleQuestions }}</strong>
                {{ $totalAccessibleQuestions === 1 ? 'pregunta' : 'preguntas' }}
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Padre</th>
                            <th>Alcance</th>
                            <th class="text-center text-nowrap">Preguntas</th>
                            <th class="text-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryRows as $row)
                            <tr>
                                <td>
                                    @if ($row->parent_id)
                                        <span class="text-muted small mr-1">└</span>
                                    @endif
                                    {{ $row->name }}
                                </td>
                                <td>{{ $row->parent->name ?? '—' }}</td>
                                <td>
                                    @if ($row->user_id)
                                        <span class="badge badge-info">Personal</span>
                                    @else
                                        <span class="badge badge-secondary">Institucional</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light text-dark">{{ $row->questions_count }}</span>
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        wire:click="openCategoryCreate({{ $row->id }})" title="Subcategoría">
                                        <i class="fas fa-level-down-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="openCategoryEdit({{ $row->id }})">Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="confirmCategoryDelete({{ $row->id }})">Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Sin categorías. Crea una raíz o importa tras definir una carpeta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($categoryRows->count() >= 400)
                <div class="card-footer small text-muted">Mostrando las primeras 400 coincidencias. Acota la búsqueda.</div>
            @endif
        </div>

        {{-- Modal categoría --}}
        <div class="modal fade @if ($showCategoryModal) show d-block @endif" tabindex="-1"
            @if ($showCategoryModal) style="display:block;background:rgba(0,0,0,.5)" @endif>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $cat_id ? 'Editar categoría' : 'Nueva categoría' }}</h5>
                        <button type="button" class="close" wire:click="$set('showCategoryModal', false)">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if (auth()->user()->hasRole('Admin') && !$cat_parent_id)
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cat-inst" wire:model.live="cat_institutional">
                                    <label class="form-check-label" for="cat-inst">
                                        Categoría institucional (visible para todos los docentes)
                                    </label>
                                </div>
                                <small class="text-muted">Si desmarcas, la carpeta queda en tu espacio personal.</small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Subcategoría de (opcional)</label>
                            <select class="form-control" wire:model="cat_parent_id">
                                <option value="">— Raíz —</option>
                                @foreach ($flatGroups as $id => $label)
                                    @if (!$cat_id || (int) $id !== (int) $cat_id)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('cat_parent_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" wire:model="cat_name">
                            @error('cat_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" rows="2" wire:model="cat_description"></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cat-act" wire:model="cat_is_active">
                            <label class="form-check-label" for="cat-act">Activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCategoryModal', false)">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="saveCategory">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Pestaña preguntas ===== --}}
    @if ($activeTab === 'questions')
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-end">
                <div class="form-inline mr-3 mb-2">
                    <label class="mr-2">Buscar</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="questionSearch"
                        placeholder="enunciado o feedback">
                </div>

                <div class="form-inline mr-3 mb-2">
                    <label class="mr-2">Categoría</label>
                    <select class="form-control form-control-sm" wire:model.live="filter_group_id" style="min-width:220px;">
                        <option value="">Todas</option>
                        @foreach ($flatGroups as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-inline mr-3 mb-2">
                    <label class="mr-2">Alcance</label>
                    <select class="form-control form-control-sm" wire:model.live="questionScope">
                        <option value="all">Todas</option>
                        <option value="institutional">Institucionales</option>
                        <option value="mine">Mis preguntas</option>
                    </select>
                </div>

                <div class="form-inline mr-3 mb-2">
                    <label class="mr-2">Por página</label>
                    <select class="form-control form-control-sm" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <button type="button" class="btn btn-primary btn-sm ml-auto mb-2" wire:click="openQuestionCreate">
                    <i class="fas fa-plus mr-1"></i> Nueva pregunta
                </button>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Importar preguntas (CSV/Excel)</div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center">
                    <a href="{{ route('questions.template.csv') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                        <i class="fas fa-download mr-1"></i> Plantilla CSV
                    </a>
                    <div class="custom-file mr-2 mb-2" style="max-width:340px;">
                        <input type="file" class="custom-file-input" id="fileImport" wire:model="file"
                            accept=".csv,.xlsx,.txt">
                        <label class="custom-file-label" for="fileImport">
                            {{ $file ? $file->getClientOriginalName() : 'Seleccionar archivo…' }}
                        </label>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm mb-2" wire:click="import" wire:loading.attr="disabled">
                        <i class="fas fa-file-import mr-1"></i> Importar
                    </button>
                </div>
                <small class="text-muted d-block">Las filas se importan a la primera categoría disponible de tu banco (configura categorías antes).</small>
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
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Alcance</th>
                            <th class="text-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questionRows as $i => $q)
                            <tr>
                                <td>{{ $questionRows->firstItem() + $i }}</td>
                                <td style="max-width:420px">{{ \Illuminate\Support\Str::limit(strip_tags($q->statement), 120) }}</td>
                                <td>
                                    @if ($q->qtype === 'short')
                                        <span class="badge badge-info">Abierta</span>
                                    @else
                                        <span class="badge badge-primary">Opción múltiple</span>
                                    @endif
                                </td>
                                <td>{{ $q->group->name ?? '—' }}</td>
                                <td>
                                    @if ($q->user_id)
                                        <span class="badge badge-info">Personal</span>
                                    @else
                                        <span class="badge badge-secondary">Institucional</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="openQuestionEdit({{ $q->id }})">Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="confirmQuestionDelete({{ $q->id }})">Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Sin preguntas con los filtros actuales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($questionRows->total() > 0)
                <div class="card-footer py-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="small text-muted mb-2 mb-lg-0">
                            Mostrando <strong>{{ $questionRows->firstItem() }}</strong>–<strong>{{ $questionRows->lastItem() }}</strong>
                            de <strong>{{ number_format($questionRows->total()) }}</strong>
                        </div>
                        @if ($questionRows->hasPages())
                            <div class="pagination-wrap">{{ $questionRows->links() }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal pregunta (CKEditor como manage-questions) --}}
        <div class="modal fade @if ($showQuestionModal) show d-block @endif" tabindex="-1"
            @if ($showQuestionModal) style="display:block;background:rgba(0,0,0,.5)" @endif
            x-data="{ open: @entangle('showQuestionModal').live }"
            x-init="$watch('open', (val) => { val ? setTimeout(() => window.initStatementEditorQB(@this), 120) : window.destroyStatementEditorQB(); })">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $q_id ? 'Editar' : 'Nueva' }} pregunta</h5>
                        <button type="button" class="close" wire:click="$set('showQuestionModal', false)">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Categoría</label>
                            <select class="form-control" wire:model.live="question_group_id">
                                <option value="">— Selecciona —</option>
                                @foreach ($flatGroups as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('question_group_id')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Tipo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="qb-qtype-m" value="multiple" wire:model.live="qtype">
                                <label class="form-check-label" for="qb-qtype-m">Opción múltiple (A–D)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="qb-qtype-s" value="short" wire:model.live="qtype">
                                <label class="form-check-label" for="qb-qtype-s">Respuesta abierta</label>
                            </div>
                        </div>

                        <div class="form-group" wire:ignore>
                            <label>Enunciado</label>
                            <div id="statement-editor-qb" class="border rounded" style="min-height: 180px;"></div>
                        </div>
                        <input type="hidden" wire:model.defer="statement">
                        @error('statement')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror

                        <div class="form-group mt-2">
                            <label>Feedback (opcional)</label>
                            <textarea class="form-control" rows="2" wire:model.defer="feedback"></textarea>
                        </div>

                        @if ($qtype === 'multiple')
                            <hr>
                            <div class="border rounded p-2">
                                @foreach ($opts as $idx => $opt)
                                    <div class="form-row align-items-center mb-2">
                                        <div class="col-auto">
                                            <span class="badge badge-secondary">{{ $opt['label'] }}</span>
                                        </div>
                                        <div class="col">
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model.defer="opts.{{ $idx }}.content"
                                                placeholder="Opción {{ $opt['label'] }}">
                                        </div>
                                        <div class="col-auto">
                                            <input class="form-check-input" type="radio" name="qb-correct"
                                                wire:click="setCorrectOption({{ $idx }})"
                                                @checked($opt['is_correct'])>
                                            <label class="small">Correcta</label>
                                        </div>
                                    </div>
                                @endforeach
                                @error('opts')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        @else
                            <div class="alert alert-info mb-0 small">
                                El docente calificará las respuestas en la ejecución de la partida.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showQuestionModal', false)">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="saveQuestion" wire:loading.attr="disabled">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('js')
        <script>
            window.__statementEditorQB = null;

            window.initStatementEditorQB = function(wire) {
                if (window.__statementEditorQB) return;
                const el = document.getElementById('statement-editor-qb');
                if (!el || typeof CKEDITOR === 'undefined') return;

                CKEDITOR.ClassicEditor.create(el, {
                        toolbar: {
                            items: [
                                'heading', '|',
                                'bold', 'italic', 'underline', 'strikethrough', '|',
                                'bulletedList', 'numberedList', '|',
                                'link', 'imageUpload', 'mediaEmbed', '|',
                                'blockQuote', 'insertTable', 'alignment', '|',
                                'undo', 'redo'
                            ]
                        },
                        removePlugins: [
                            'CloudServices', 'CKBox', 'CKBoxUtils', 'CKBoxImageEdit',
                            'CKFinder', 'CKFinderUploadAdapter', 'EasyImage',
                            'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
                            'RealTimeCollaborativeRevisionHistory', 'PresenceList',
                            'Comments', 'TrackChanges', 'TrackChangesData', 'RevisionHistory',
                            'WProofreader', 'MathType', 'ExportPdf', 'ExportWord',
                            'Pagination', 'MultiLevelList',
                            'AIAssistant', 'AiAssistant', 'AI',
                            'SlashCommand', 'Template', 'DocumentOutline',
                            'FormatPainter', 'TableOfContents', 'PasteFromOfficeEnhanced', 'CaseChange',
                            'Unsplash'
                        ],
                        image: {
                            insert: { integrations: ['upload'] },
                            toolbar: [
                                'imageTextAlternative', 'toggleImageCaption',
                                'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'
                            ]
                        },
                        simpleUpload: {
                            uploadUrl: "{{ route('ckeditor.upload') }}",
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                            }
                        },
                        mediaEmbed: { previewsInData: true }
                    })
                    .then(editor => {
                        window.__statementEditorQB = editor;
                        editor.setData(wire.get('statement') || '');
                        editor.model.document.on('change:data', () => {
                            wire.set('statement', editor.getData());
                        });
                        const editableEl = editor.ui.view.editable.element;
                        editableEl.setAttribute('tabindex', '0');
                        ['keydown', 'keypress', 'keyup', 'input', 'paste'].forEach(evt => {
                            editableEl.addEventListener(evt, e => e.stopPropagation(), { capture: true });
                        });
                    })
                    .catch(err => console.error('CKEditor QB:', err));
            };

            window.destroyStatementEditorQB = function() {
                if (window.__statementEditorQB) {
                    window.__statementEditorQB.destroy().then(() => window.__statementEditorQB = null);
                }
            };
        </script>
    @endpush
</div>
