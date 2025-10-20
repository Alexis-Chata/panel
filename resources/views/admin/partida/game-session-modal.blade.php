<div class="modal fade" id="gameSessionModal" tabindex="-1" aria-labelledby="gameSessionModalLabel"
     aria-hidden="true" data-backdrop="static" data-keyboard="false" wire:ignore.self>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="gameSessionModalLabel" class="modal-title">
          {{ $form->gameSession?->id ? 'Editar Partida' : 'Nueva Partida' }}
        </h5>
        <button type="button" class="close" id="cerrar_modal_gamesession_x" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form wire:submit="save_session">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-12">
              <label>Título</label>
              <input type="text" class="form-control" wire:model.defer="form.title" placeholder="Ej. Panel Básico">
              @error('form.title') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
              <label>N° preguntas</label>
              <input type="number" class="form-control" min="1" max="50" wire:model.defer="form.questions_total">
              @error('form.questions_total') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
              <label>Tiempo por pregunta (s)</label>
              <input type="number" class="form-control" min="5" max="600" wire:model.defer="form.timer_default">
              @error('form.timer_default') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-12">
              <label>Vista estudiante</label>
              <select class="form-control" wire:model.defer="form.student_view_mode">
                <option value="completo">Enunciado + alternativas</option>
                <option value="solo_alternativas">Solo alternativas</option>
              </select>
              @error('form.student_view_mode') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Opcional: mostrar el code generado (solo lectura) --}}
            @if($form->gameSession?->code)
              <div class="form-group col-md-12">
                <label>Código de sesión</label>
                <input type="text" class="form-control" value="{{ $form->gameSession->code }}" readonly>
              </div>
            @endif
          </div>
          {{-- ... campos existentes del modal ... --}}
            <div class="form-group">
                <label>Adjuntar archivos (opcional)</label>
                <input type="file"
                        class="form-control-file"
                        wire:model.live="uploads"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.ppt,.pptx,.zip">
                @error('uploads.*') <small class="text-danger">{{ $message }}</small> @enderror

                {{-- Preview simple de seleccionados --}}
                @if (!empty($uploads))
                    <ul class="mt-2 small">
                    @foreach ($uploads as $u)
                        <li>{{ $u->getClientOriginalName() }} ({{ number_format($u->getSize()/1024, 0) }} KB)</li>
                    @endforeach
                    </ul>
                @endif

                {{-- Progreso mientras sube --}}
                <div wire:loading wire:target="uploads" class="small text-muted mt-1">
                    Subiendo archivos…
                </div>
            </div>
            {{-- Archivos existentes (solo al editar) --}}
            @if ($form->gameSession?->id)
                @php $existing = $form->gameSession->archivos()->latest()->get(); @endphp

                @if ($existing->count())
                    <hr>
                    <label class="d-block">Archivos actuales</label>

                    <ul class="list-group mb-2">
                    @foreach ($existing as $file)
                        @php
                        $isImage = Str::of($file->url)->lower()->match('/\.(jpg|jpeg|png|webp)$/');
                        $filename = basename($file->url);
                        @endphp

                        <li class="list-group-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            @if ($isImage)
                            <img src="{{ asset($file->url) }}" alt="adjunto" style="width:48px;height:48px;object-fit:cover" class="rounded mr-2">
                            @else
                            <i class="far fa-file mr-2"></i>
                            @endif
                            <a href="{{ asset($file->url) }}" target="_blank" class="text-truncate" style="max-width:260px;">
                            {{ $filename }}
                            </a>
                        </div>

                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                wire:click="removeArchivo({{ $file->id }})"
                                wire:confirm="¿Eliminar el archivo '{{ $filename }}'?"
                                wire:target="removeArchivo({{ $file->id }})"
                                wire:loading.attr="disabled">
                            <i class="fas fa-trash"></i>
                        </button>
                        </li>
                    @endforeach
                    </ul>
                @endif
            @endif

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary"
                  wire:target="save_session"
                  wire:loading.attr="disabled">
            Guardar
          </button>
        </div>
      </form>
    </div>
  </div>

  @script
    <script>
      document.addEventListener('livewire:initialized', () => {
        Livewire.on('cerrar_modal_gamesession', () => {
          document.getElementById('cerrar_modal_gamesession_x')?.click();
        });
      });
    </script>
  @endscript
</div>
