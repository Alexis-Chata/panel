<div class="container py-3" x-data="{ showForm: @entangle('showForm') }"> {{-- ÚNICO ROOT --}}

    {{-- Nueva partida (header) --}}
    <div class="card mb-3">
        <div class="card-header">Nueva partida</div>
        <div class="card-body d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Crea una partida y configura fases, fechas y ajustes.
            </div>
            <div>
                <button class="btn btn-success" @click="showForm = true" wire:click="openCreateForm">
                    Nueva partida
                </button>
            </div>
        </div>
    </div>

    {{-- CARD: Formulario de creación (aparece con Alpine) --}}
    <div class="card mb-3" x-show="showForm" x-transition>
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Configurar nueva partida</span>
            <button class="btn btn-outline-secondary btn-sm" @click="showForm=false"
                wire:click="cancelCreate">Cancelar</button>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Título / Estado / Fase actual / Counts --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model.defer="form.title"
                            placeholder="Ej: Partida de Matemática">
                        @error('form.title')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado <span class="text-danger">*</span></label>
                        <select class="form-control" wire:model.defer="form.status">
                            @foreach (['draft', 'lobby', 'phase1', 'phase2', 'phase3', 'results', 'finished'] as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                        @error('form.status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fase actual (0–3) <span class="text-danger">*</span></label>
                        <input type="number" min="0" max="3" class="form-control"
                            wire:model.defer="form.current_phase">
                        @error('form.current_phase')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Preguntas Fase 1</label>
                        <input type="number" min="0" class="form-control" wire:model.defer="form.phase1_count">
                        @error('form.phase1_count')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Preguntas Fase 2</label>
                        <input type="number" min="0" class="form-control" wire:model.defer="form.phase2_count">
                        @error('form.phase2_count')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Preguntas Fase 3</label>
                        <input type="number" min="0" class="form-control" wire:model.defer="form.phase3_count">
                        @error('form.phase3_count')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Fechas --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Inicia en</label>
                        <input type="datetime-local" class="form-control" wire:model.defer="form.starts_at">
                        @error('form.starts_at')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Termina en</label>
                        <input type="datetime-local" class="form-control" wire:model.defer="form.ends_at">
                        @error('form.ends_at')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fin de fase actual</label>
                        <input type="datetime-local" class="form-control" wire:model.defer="form.phase_ends_at">
                        @error('form.phase_ends_at')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Settings JSON --}}
                <div class="col-12">
                    <div class="form-group">
                        <label>Settings (JSON)</label>
                        <textarea rows="3" class="form-control" wire:model.defer="form.settings_json"
                            placeholder='{"phase1":{"time_limit":60},"points":{"correct":2,"wrong":0}}'></textarea>
                        @error('form.settings_json')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">Config avanzada (tiempos, puntos, etc.).</small>
                    </div>
                </div>
            </div>

            <hr>

            {{-- ======= Repeater por fase: Pools + Pesos ======= --}}
            @foreach ([1, 2, 3] as $phase)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-2">Fase {{ $phase }} — distribución de pools (la suma de pesos debe ser
                            100)</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm"
                            wire:click="addPoolRow({{ $phase }})">
                            Agregar pool
                        </button>
                    </div>

                    @error("form.pools.$phase")
                        <div class="text-danger mb-2">{{ $message }}</div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th style="width:60%">Question Pool</th>
                                    <th style="width:25%">Peso (%)</th>
                                    <th class="text-right" style="width:15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($form->pools[$phase] as $i => $row)
                                    <tr>
                                        <td>
                                            <select class="form-control"
                                                wire:model.defer="form.pools.{{ $phase }}.{{ $i }}.question_pool_id">
                                                <option value="">-- seleccionar --</option>
                                                @foreach ($questionPools as $qp)
                                                    <option value="{{ $qp->id }}">{{ $qp->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("form.pools.$phase.$i.question_pool_id")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" min="1" max="100" class="form-control"
                                                wire:model.defer="form.pools.{{ $phase }}.{{ $i }}.weight">
                                            @error("form.pools.$phase.$i.weight")
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                wire:click="removePoolRow({{ $phase }}, {{ $i }})">
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">Sin pools aún…</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

        </div>

        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary" wire:click="save">Guardar y abrir lobby</button>
        </div>
    </div>


    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-header">Filtros</div>
        <div class="card-body row g-2">
            <div class="col-md-6">
                <input class="form-control" placeholder="Buscar por código o título"
                    wire:model.live.debounce.400ms="q">
            </div>
            <div class="col-md-3">
                <select class="form-control" wire:model.live="status">
                    <option value="">Todos los estados</option>
                    <option value="draft">draft</option>
                    <option value="lobby">lobby</option>
                    <option value="phase1">phase1</option>
                    <option value="phase2">phase2</option>
                    <option value="phase3">phase3</option>
                    <option value="results">results</option>
                    <option value="finished">finished</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control" wire:model.live="perPage">
                    <option value="10">10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabla (igual a tu versión, solo ajusté form-control vs form-select por BS4.6) --}}
    <div class="card">
        <div class="card-header">Partidas</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Fase</th>
                        <th>Jugadores</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                        <tr data-session-id="{{ $s->id }}">
                            <td>{{ $s->id }}</td>
                            <td>
                                <code class="mr-2">{{ $s->code }}</code>
                                <button class="btn btn-outline-secondary btn-xs" data-copy="{{ $s->code }}"
                                    title="Copiar código">Copiar</button>
                            </td>
                            <td>{{ $s->title }}</td>
                            <td><span class="badge badge-info">{{ $s->status }}</span></td>
                            <td>{{ $s->current_phase }}</td>
                            <td>{{ $s->participants_count }}</td>
                            <td class="text-end">
                                <a class="btn btn-primary btn-sm"
                                    href="{{ route('admin.sessions.lobby', $s) }}">Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted p-3">No hay partidas para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sessions->hasPages())
            <div class="card-footer">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    {{-- Suscripción a eventos realtime para refrescar la lista --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- estado global anti-duplicados / vida del observer ---
            window.__sessionsIndexSubs = window.__sessionsIndexSubs || new Set();
            window.__sessionsIndexMO = window.__sessionsIndexMO || null;

            const collectIdsFromDom = (root = document) =>
                Array.from(root.querySelectorAll('tbody tr[data-session-id]'))
                .map(tr => Number(tr.getAttribute('data-session-id')))
                .filter(Boolean);

            const subscribeOne = (id) => {
                if (window.__sessionsIndexSubs.has(id)) return;
                window.__sessionsIndexSubs.add(id);

                const chScores = window.Echo?.private(`sessions.${id}.scores`);
                const chPhase = window.Echo?.private(`sessions.${id}.phase`);
                const chParticipants = window.Echo?.private(`sessions.${id}.participants`);

                chScores?.listen('.ScoreUpdated', () => window.Livewire?.dispatch('sessions-index-refresh', {
                    session_id: id
                }));
                chPhase?.listen('.SessionPhaseChanged', () => window.Livewire?.dispatch(
                    'sessions-index-refresh', {
                        session_id: id
                    }));
                chParticipants?.listen('.ParticipantUpdated', () => window.Livewire?.dispatch(
                    'sessions-index-refresh', {
                        session_id: id
                    }));
            };

            // Re-escaneo con coalescing (evita scans repetidos durante un mismo ciclo)
            let scanScheduled = false;
            const rescan = (root = document) => {
                if (scanScheduled) return;
                scanScheduled = true;
                requestAnimationFrame(() => {
                    collectIdsFromDom(root).forEach(subscribeOne);
                    scanScheduled = false;
                });
            };

            // 1) Primer escaneo
            rescan();

            // 2) Re-escaneo tras cada morph de Livewire (fiable con Livewire 3)
            if (window.Livewire?.hook) {
                window.Livewire.hook('morph.updated', ({
                    el
                }) => rescan(el));
            }

            // 3) MutationObserver como “red de seguridad”
            //    Observa un contenedor estable; si cambia algo en su subárbol, re-escanea.
            if (!window.__sessionsIndexMO) {
                const container = document.querySelector('.table-responsive') || document;
                const mo = new MutationObserver(() => rescan(container));
                mo.observe(container, {
                    childList: true,
                    subtree: true
                });
                window.__sessionsIndexMO = mo;
            }

            // 4) Limpieza al salir (opcional pero sano)
            window.addEventListener('beforeunload', () => {
                window.__sessionsIndexMO?.disconnect();
                window.__sessionsIndexMO = null;
            }, {
                once: true
            });
        });
    </script>

    {{-- JS para copiar código (opcional SweetAlert si lo tienes cargado) --}}
    <script>
        async function copyText(text) {
            // 1) Intento moderno si hay contexto seguro
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
            // 2) Fallback para HTTP/lan o navegadores viejos
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '-9999px';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();

            let ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(ta);
            return ok;
        }

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;

            const code = btn.getAttribute('data-copy') ?? '';
            try {
                const ok = await copyText(code);
                const msg = ok ? {
                    title: 'Copiado',
                    text: code,
                    icon: 'success'
                } : {
                    title: 'Error',
                    text: 'No se pudo copiar',
                    icon: 'error'
                };

                if (window.Swal) {
                    Swal.fire({
                        ...msg,
                        timer: 900,
                        showConfirmButton: false
                    });
                } else {
                    alert(`${msg.title}: ${msg.text}`);
                }
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Error',
                        text: String(err),
                        icon: 'error'
                    });
                } else {
                    alert('Error: ' + err);
                }
            }
        }, {
            passive: true
        });
    </script>

</div>
