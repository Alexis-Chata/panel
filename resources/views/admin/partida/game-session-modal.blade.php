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
