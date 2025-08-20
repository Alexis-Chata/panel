<div class="container py-3">
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-1">
                {{ $session->title }}
                <small class="text-muted">[{{ $session->status }}]</small>
            </h4>
            <div>Mi puntaje: <b>{{ $participant->total_score }}</b></div>
        </div>
    </div>

    @if (!$current)
        <div class="alert alert-info">No hay más preguntas por ahora. Espera instrucciones…</div>
    @else
        <div class="card">
            <div class="card-header">
                Pregunta #{{ $current->order }} (fase {{ $current->phase }})
            </div>
            <div class="card-body">
                <p class="lead mb-3">{{ $current->question->stem }}</p>

                @php($type = $current->question->type)

                {{-- Enviamos TODO en un único submit --}}
                <form wire:submit.prevent="answer">
                    @if (in_array($type, ['single', 'boolean']))
                        @foreach ($current->question->options()->orderBy('order')->get() as $idx => $opt)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answer_option"
                                    id="opt{{ $opt->id }}" value="{{ $opt->id }}"
                                    wire:model.defer="selectedOptionId"
                                    @if ($idx === 0) required @endif>
                                <label class="form-check-label" for="opt{{ $opt->id }}">
                                    {{ $opt->label }}
                                </label>
                            </div>
                        @endforeach
                    @elseif(in_array($type, ['numeric', 'text']))
                        <div class="mb-2">
                            <input type="{{ $type === 'numeric' ? 'number' : 'text' }}" class="form-control"
                                placeholder="{{ $type === 'numeric' ? 'Ingresa un número' : 'Escribe tu respuesta' }}"
                                wire:model.defer="freeText" required>
                        </div>
                    @else
                        <div class="text-muted">Tipo de pregunta no soportado aún.</div>
                    @endif

                    <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled">
                        Enviar
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- JS de suscripciones y SweetAlert, dentro del root para mantener un solo top-level --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sessionId = @json($session->id);

            // Canales privados
            window.Echo?.private(`sessions.${sessionId}.scores`)
                .listen('.ScoreUpdated', (e) => window.Livewire?.dispatch('score-updated', e));

            window.Echo?.private(`sessions.${sessionId}.phase`)
                .listen('.SessionPhaseChanged', () => window.Livewire?.dispatch('phase-changed'));

            // SweetAlert puente (desde $this->dispatch('swal', ...))
            window.addEventListener('swal', (evt) => {
                const {
                    title,
                    icon
                } = evt.detail || {};
                window.Swal?.fire({
                    title,
                    icon,
                    timer: 1000,
                    showConfirmButton: false
                });
            });
        });
    </script>
</div>
