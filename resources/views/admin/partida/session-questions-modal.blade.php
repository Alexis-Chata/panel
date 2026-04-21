<div class="modal fade" id="sessionQuestionsModal" tabindex="-1" aria-labelledby="sessionQuestionsModalLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 id="sessionQuestionsModalLabel" class="modal-title mb-0">Preguntas del cuestionario</h5>
                    @if ($compositionSession)
                        <small class="text-muted">{{ $compositionSession->title }}</small>
                        <span class="badge badge-secondary ml-1">{{ $compositionSession->code }}</span>
                    @endif
                </div>
                <button type="button" class="close" wire:click="closeConfigureQuestions" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form wire:submit="save_question_composition">
                <div class="modal-body">
                    {{-- 1. Modo de armado (primero, como pediste) --}}
                    <div class="border rounded-lg p-3 mb-4 bg-light">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-layer-group mr-2"></i>¿Cómo armar el
                            cuestionario?</h6>
                        <div class="form-group mb-0">
                            <select class="form-control" wire:model.live="questionPickMode">
                                <option value="random">Solo aleatorias (desde la categoría)</option>
                                <option value="manual">Solo elegidas por ti (orden de selección)</option>
                                <option value="mixed">Mixto: fijas + el resto aleatorio (estilo Moodle)</option>
                            </select>
                            <small class="text-muted d-block mt-2">
                                En <strong>mixto</strong>, primero van las que marques; después se completan al azar.
                            </small>
                        </div>
                    </div>

                    {{-- 2. Categoría y cantidades --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Categoría (banco)</label>
                        <select class="form-control" wire:model.live="compositionQuestionGroupId">
                            <option value="">— Selecciona una categoría —</option>
                            @foreach ($questionGroups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            Puedes cambiar de categoría y seguir marcando; las ya seleccionadas se conservan.
                        </small>
                        @error('compositionQuestionGroupId')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                        @if ($compositionQuestionGroupId)
                            <small class="text-muted">Preguntas disponibles:
                                <strong>{{ $poolAvailableCount }}</strong></small>
                        @endif
                    </div>

                    @if ($compositionQuestionGroupId)
                        @if ($questionPickMode === 'random')
                            <div class="form-group">
                                <label>Cuántas preguntas aleatorias</label>
                                <input type="number" class="form-control" min="1" max="255"
                                    wire:model.defer="form.questions_total">
                                @error('form.questions_total')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        @elseif($questionPickMode === 'manual')
                            <div class="form-group">
                                <label class="d-block">Total</label>
                                <p class="small text-muted mb-0">
                                    Igual al número de preguntas marcadas abajo
                                    @if (count($selectedQuestionIds ?? []) > 0)
                                        (<strong>{{ count($selectedQuestionIds) }}</strong> seleccionadas)
                                    @endif
                                </p>
                            </div>
                        @else
                            <div class="form-group">
                                <p class="small text-muted mb-2">
                                    <strong>{{ count($selectedQuestionIds ?? []) }}</strong> fijas +
                                    <strong>{{ $randomExtraCount ?? 0 }}</strong> aleatorias =
                                    <strong>{{ count($selectedQuestionIds ?? []) + ($randomExtraCount ?? 0) }}</strong>
                                    preguntas
                                </p>
                                @error('randomExtraCount')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                                <label class="small">Preguntas aleatorias adicionales</label>
                                <input type="number" class="form-control form-control-sm" min="0" max="500"
                                    wire:model.live="randomExtraCount" style="max-width:140px">
                            </div>
                        @endif

                        {{-- 3. Lista de preguntas (manual / mixto) --}}
                        @if (in_array($questionPickMode, ['manual', 'mixed'], true))
                            <div class="form-group">
                                <label class="font-weight-bold">Elegir del banco</label>
                                <input type="text" class="form-control form-control-sm mb-2"
                                    wire:model.live.debounce.300ms="poolQuestionSearch"
                                    placeholder="Buscar en el enunciado…">
                                <div class="border rounded p-2"
                                    style="max-height: 280px; overflow: auto; background: rgba(0,0,0,.03);">
                                    @forelse ($poolQuestions as $pq)
                                        <div class="form-check mb-1 d-flex align-items-start session-question-row">
                                            <input class="form-check-input mt-1 session-question-check" type="checkbox"
                                                wire:key="comp-pick-{{ $pq->id }}"
                                                wire:click="togglePoolQuestion({{ $pq->id }})"
                                                id="compPq{{ $pq->id }}"
                                                @checked(in_array($pq->id, $selectedQuestionIds ?? []))>
                                            <label class="form-check-label small" for="compPq{{ $pq->id }}"
                                                style="cursor: pointer;">
                                                <span
                                                    class="badge badge-{{ $pq->qtype === 'short' ? 'info' : 'primary' }} badge-pill mr-1">{{ $pq->qtype === 'short' ? 'A' : 'OM' }}</span>
                                                {{ \Illuminate\Support\Str::limit(strip_tags($pq->statement), 140) }}
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">No hay preguntas o ajusta la búsqueda.</p>
                                    @endforelse
                                </div>
                                @error('selectedQuestionIds')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                                <small class="text-muted">Marcadas:
                                    <strong>{{ count($selectedQuestionIds ?? []) }}</strong></small>
                            </div>
                        @endif
                    @endif

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="closeConfigureQuestions">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" wire:target="save_question_composition"
                        wire:loading.attr="disabled"
                        @if (!$compositionQuestionGroupId) disabled @endif>
                        Guardar cuestionario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
