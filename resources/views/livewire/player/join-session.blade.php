<div class="game-join-bg py-5">
  <div class="container">
    {{-- HERO --}}
    <div class="game-hero mb-4">
      <div class="d-flex align-items-center gap-3">
        <div class="game-hero-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <div>
          <h1 class="h3 mb-1 fw-bold">Únete a la partida educativa</h1>
          <p class="mb-0">Refuerza tus conocimientos y gana XP respondiendo preguntas.</p>
        </div>
        <div class="ms-auto d-none d-md-flex align-items-center gap-2">
          <span class="badge bg-warning text-dark"><i class="fas fa-bolt me-1"></i> +100 XP</span>
          <span class="badge bg-info"><i class="fas fa-clock me-1"></i> 10–15 min</span>
          <span class="badge bg-success"><i class="fas fa-users me-1"></i> 2–30</span>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <div class="card game-card shadow-lg border-0">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center mb-3">
              <span class="game-badge me-2"><i class="fas fa-puzzle-piece"></i></span>
              <div>
                <h2 class="h4 mb-0 fw-bold">Ingresa el código de la sala</h2>
                <small class="text-muted">Son 6 caracteres (letras y números)</small>
              </div>
            </div>

            <div class="input-group input-group-lg mt-3">
              <span class="input-group-text bg-transparent border-0 px-0">
                <i class="fas fa-key fa-lg text-primary"></i>
              </span>
              <input
                type="text"
                class="form-control form-control-lg code-input"
                maxlength="6"
                placeholder="ABC123"
                autocomplete="off"
                inputmode="latin"
                wire:model.debounce.300ms="code"
                wire:keydown.enter="join"
              >
              <button class="btn btn-primary btn-lg px-4"
                      wire:click="join"
                      wire:loading.attr="disabled">
                <span wire:loading.remove><i class="fas fa-play me-2"></i>Unirme</span>
                <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span>Conectando…</span>
              </button>
            </div>

            {{-- feedback de error/validación --}}
            @error('code')
              <div class="small text-danger mt-2"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
            @if($error)
              <div class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0">
                <i class="fas fa-times-circle"></i>
                <div>{{ $error }}</div>
              </div>
            @endif

            {{-- Tips gamificados --}}
            <div class="row g-3 mt-4">
              <div class="col-md-4">
                <div class="feature-tile">
                  <i class="fas fa-hat-wizard"></i>
                  <div class="fw-semibold">Aprende jugando</div>
                  <small class="text-muted">Preguntas cortas, feedback inmediato.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="feature-tile">
                  <i class="fas fa-medal"></i>
                  <div class="fw-semibold">Suma XP y logros</div>
                  <small class="text-muted">Gana medallas por rachas correctas.</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="feature-tile">
                  <i class="fas fa-shield-alt"></i>
                  <div class="fw-semibold">Justo y seguro</div>
                  <small class="text-muted">Salas privadas con código.</small>
                </div>
              </div>
            </div>

          </div>
        </div>

        {{-- Fila de ayuda rápida --}}
        <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center">
          <span class="badge rounded-pill text-bg-light"><i class="fas fa-keyboard me-1"></i> Presiona Enter para unirte</span>
          <span class="badge rounded-pill text-bg-light"><i class="fas fa-qrcode me-1"></i> Próximamente: unirte por QR</span>
        </div>
      </div>
    </div>
  </div>
</div>
