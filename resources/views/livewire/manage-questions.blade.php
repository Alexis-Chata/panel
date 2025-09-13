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
                            <td style="max-width:600px">{{ Str::limit(strip_tags($q->statement), 120) }}</td>
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
    <div
        class="modal fade @if ($showModal) show d-block @endif"
        tabindex="-1" role="dialog"
        @if ($showModal) style="display:block;background:rgba(0,0,0,.5)" @endif
        x-data="{ open: @entangle('showModal').live }"
        x-init="$watch('open', (val) => { val ? setTimeout(()=>window.initStatementEditor(@this), 120) : window.destroyStatementEditor(); })"
        {{-- OJO: SIN wire:ignore.self AQUÍ --}}
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $q_id ? 'Editar' : 'Nueva' }} Pregunta</h5>
                    <button type="button" class="close" aria-label="Close" wire:click="$set('showModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    {{-- ENUNCIADO (CKEditor) --}}
                    <div class="form-group" wire:ignore> {{-- wire:ignore SOLO AQUÍ --}}
                        <label>Enunciado</label>
                        <div id="statement-editor" class="border rounded" style="min-height: 180px;"></div>
                    </div>
                    <input type="hidden" wire:model.defer="statement">

                    @error('statement')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                    {{-- FEEDBACK (puede quedarse como textarea simple o también hacerlo CKEditor si quieres) --}}
                    <div class="form-group">
                        <label>Feedback (opcional)</label>
                        <textarea class="form-control" rows="2" wire:model.defer="feedback"></textarea>
                        @error('feedback')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- ... resto (opciones A-D) ... --}}

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="$set('showModal', false)">Cerrar</button>
                    <button class="btn btn-primary" wire:click="save">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            window.__statementEditor = null;

            window.initStatementEditor = function (wire) {
                if (window.__statementEditor) return;

                const el = document.getElementById('statement-editor');
                if (!el) return;

                CKEDITOR.ClassicEditor.create(el, {
                    toolbar: {
                        items: [
                            'heading','|',
                            'bold','italic','underline','strikethrough','|',
                            'bulletedList','numberedList','|',
                            'link','imageUpload','mediaEmbed','|',
                            'blockQuote','insertTable','alignment','|',
                            'undo','redo'
                        ]
                    },

                    // 👉 Desactiva TODO lo que pide licencia o servicios cloud
                    removePlugins: [
                        // Cloud / CKBox / CKFinder
                        'CloudServices','CKBox','CKBoxUtils','CKBoxImageEdit',
                        'CKFinder','CKFinderUploadAdapter','EasyImage',

                        // Colaboración / comentarios / track changes
                        'RealTimeCollaborativeComments','RealTimeCollaborativeTrackChanges',
                        'RealTimeCollaborativeRevisionHistory','PresenceList',
                        'Comments','TrackChanges','TrackChangesData','RevisionHistory',

                        // Premium varios
                        'WProofreader','MathType','ExportPdf','ExportWord',
                        'Pagination','MultiLevelList', // <- tus errores de licencia
                        'AIAssistant','AiAssistant','AI', // por si alguna variante se incluye

                        // Integraciones que no usarás
                        'SlashCommand','Template','DocumentOutline',
                        'FormatPainter','TableOfContents','PasteFromOfficeEnhanced','CaseChange',
                        'Unsplash' // algunas super-builds lo traen como integración de imagen
                    ],

                    // 👉 Fuerza que "Insertar imagen" use SOLO subida (nada de CKBox/CKFinder)
                    image: {
                        insert: { integrations: [ 'upload' ] },
                        toolbar: [
                            'imageTextAlternative','toggleImageCaption',
                            'imageStyle:inline','imageStyle:block','imageStyle:side'
                        ]
                    },

                    // 👉 Subida simple a tu endpoint Laravel
                    simpleUpload: {
                        uploadUrl: "{{ route('ckeditor.upload') }}",
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    },

                    // 👉 Para YouTube pegando la URL
                    mediaEmbed: { previewsInData: true }
                })
                .then(editor => {
                        window.__statementEditor = editor;

                        // Cargar valor inicial
                        editor.setData(wire.get('statement') || '');

                        // Sync hacia Livewire
                        editor.model.document.on('change:data', () => {
                            wire.set('statement', editor.getData());
                        });

                        // --- 🔒 Evitar que Livewire/Alpine/Bootstrap capten las teclas del editor ---
                        const editableEl = editor.ui.view.editable.element;
                        editableEl.setAttribute('tabindex', '0'); // asegúrate de poder focusear

                        ['keydown', 'keypress', 'keyup', 'input', 'paste'].forEach(evt => {
                            editableEl.addEventListener(evt, e => {
                                e.stopPropagation();
                            }, { capture: true });
                        });

                        // Por si algún plugin lo dejó en solo-lectura:
                        try { editor.isReadOnly = false; } catch (_) {}
                    })

                .catch(err => {
                    console.error('CKEditor init error:', err);
                });
            };

            window.destroyStatementEditor = function () {
                if (window.__statementEditor) {
                    window.__statementEditor.destroy().then(() => window.__statementEditor = null);
                }
            };
        </script>
    @endpush
</div>
