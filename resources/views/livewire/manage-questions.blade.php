<div x-data
    x-on:confirm-delete.window="
        if (confirm('¿Eliminar esta pregunta?')) { Livewire.dispatch('delete-now'); }
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
                    placeholder="enunciado o feedback">
            </div>

            <div class="form-inline mr-3 mb-2">
                <label class="mr-2">Grupo</label>
                <select class="form-control form-control-sm" wire:model.live="question_group_id">
                    <option value="">Todos</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
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
                <i class="fas fa-plus mr-1"></i> Nueva Pregunta
            </button>
        </div>
    </div>

    {{-- Importar --}}
    <div class="card mb-3">
        <div class="card-header">Importar preguntas (CSV/Excel)</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center">
                <a href="{{ route('questions.template.csv') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                    <i class="fas fa-download mr-1"></i> Descargar plantilla CSV
                </a>

                <div class="custom-file mr-2 mb-2" style="max-width:340px;">
                    <input type="file" class="custom-file-input" id="fileImport" wire:model="file"
                        accept=".csv,.xlsx,.txt">
                    <label class="custom-file-label" for="fileImport">
                        {{ $file ? $file->getClientOriginalName() : 'Seleccionar archivo…' }}
                    </label>
                </div>

                <button class="btn btn-primary btn-sm mb-2" wire:click="import" wire:loading.attr="disabled">
                    <i class="fas fa-file-import mr-1"></i> Importar
                </button>
            </div>
            @error('file')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Enunciado</th>
                        <th>Feedback</th>
                        <th>Tipo</th>
                        <th>Grupo</th>
                        <th class="text-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $q)
                        <tr>
                            <td>{{ $rows->firstItem() + $i }}</td>
                            <td style="max-width:600px">
                                {{ Str::limit(strip_tags($q->statement), 120) }}
                            </td>
                            <td style="max-width:400px">
                                {{ Str::limit($q->feedback, 80) }}
                            </td>
                            <td>
                                @if ($q->qtype === 'short')
                                    <span class="badge badge-info">Abierta</span>
                                @else
                                    <span class="badge badge-primary">Opción múltiple</span>
                                @endif
                            </td>
                            <td>{{ $q->group->name ?? '-' }}</td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="openEdit({{ $q->id }})">
                                    Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="confirmDelete({{ $q->id }})">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Sin registros</td>
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
        @if ($showModal) style="display:block;background:rgba(0,0,0,.5)" @endif x-data="{ open: @entangle('showModal').live }"
        x-init="$watch('open', (val) => { val ? setTimeout(() => window.initStatementEditor(@this), 120) : window.destroyStatementEditor(); })">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $q_id ? 'Editar' : 'Nueva' }} Pregunta
                    </h5>
                    <button type="button" class="close" aria-label="Close" wire:click="$set('showModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    {{-- Grupo de preguntas --}}
                    <div class="form-group">
                        <label>Grupo de preguntas</label>
                        <select class="form-control" wire:model.live="question_group_id">
                            <option value="">— Selecciona un grupo —</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        @error('question_group_id')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Tipo de pregunta --}}
                    <div class="form-group">
                        <label>Tipo de pregunta</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="qtype-multiple" value="multiple"
                                wire:model.live="qtype">
                            <label class="form-check-label" for="qtype-multiple">
                                Opción múltiple (A, B, C, D)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="qtype-short" value="short"
                                wire:model.live="qtype">
                            <label class="form-check-label" for="qtype-short">
                                Respuesta abierta (texto, será revisada por el docente)
                            </label>
                        </div>
                        @error('qtype')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- ENUNCIADO (CKEditor) --}}
                    <div class="form-group" wire:ignore>
                        <label>Enunciado</label>
                        <div id="statement-editor" class="border rounded" style="min-height: 180px;"></div>
                    </div>
                    <input type="hidden" wire:model.defer="statement">
                    @error('statement')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror

                    {{-- FEEDBACK --}}
                    <div class="form-group mt-2">
                        <label>Feedback (opcional)</label>
                        <textarea class="form-control" rows="2" wire:model.defer="feedback"></textarea>
                        @error('feedback')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Alternativas SOLO si es multiple --}}
                    @if ($qtype === 'multiple')
                        <hr>
                        <div class="form-group mb-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="mb-0">Alternativas</label>
                                <small class="text-muted">
                                    Marca exactamente una como correcta
                                </small>
                            </div>
                        </div>

                        <div class="border rounded p-2">
                            @foreach ($opts as $idx => $opt)
                                <div class="form-row align-items-center mb-2">
                                    <div class="col-auto">
                                        <span class="badge badge-secondary">
                                            {{ $opt['label'] }}
                                        </span>
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control form-control-sm"
                                            wire:model.defer="opts.{{ $idx }}.content"
                                            placeholder="Contenido de la opción {{ $opt['label'] }}">
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" name="correctOption"
                                                id="correct-{{ $idx }}"
                                                wire:click="setCorrectOption({{ $idx }})"
                                                @checked($opt['is_correct'])>
                                            <label class="form-check-label small" for="correct-{{ $idx }}">
                                                Correcta
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @error('opts')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    @else
                        <hr>
                        <div class="alert alert-info mb-0">
                            Esta será una <strong>pregunta abierta</strong>:
                            el participante escribirá su respuesta y luego
                            el docente la calificará manualmente en la ejecución
                            de la sesión.
                        </div>
                    @endif

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

    @push('js')
        <script>
            window.__statementEditor = null;

            window.initStatementEditor = function(wire) {
                if (window.__statementEditor) return;

                const el = document.getElementById('statement-editor');
                if (!el) return;

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
                            insert: {
                                integrations: ['upload']
                            },
                            toolbar: [
                                'imageTextAlternative', 'toggleImageCaption',
                                'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'
                            ]
                        },
                        simpleUpload: {
                            uploadUrl: "{{ route('ckeditor.upload') }}",
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute(
                                    'content')
                            }
                        },
                        mediaEmbed: {
                            previewsInData: true
                        }
                    })
                    .then(editor => {
                        window.__statementEditor = editor;

                        // Cargar valor inicial
                        editor.setData(wire.get('statement') || '');

                        // Sync hacia Livewire
                        editor.model.document.on('change:data', () => {
                            wire.set('statement', editor.getData());
                        });

                        // Evitar que Livewire/Alpine/Bootstrap se traguen las teclas del editor
                        const editableEl = editor.ui.view.editable.element;
                        editableEl.setAttribute('tabindex', '0');

                        ['keydown', 'keypress', 'keyup', 'input', 'paste'].forEach(evt => {
                            editableEl.addEventListener(evt, e => {
                                e.stopPropagation();
                            }, {
                                capture: true
                            });
                        });

                        try {
                            editor.isReadOnly = false;
                        } catch (_) {}
                    })
                    .catch(err => {
                        console.error('CKEditor init error:', err);
                    });
            };

            window.destroyStatementEditor = function() {
                if (window.__statementEditor) {
                    window.__statementEditor.destroy().then(() => window.__statementEditor = null);
                }
            };
        </script>
    @endpush
</div>
