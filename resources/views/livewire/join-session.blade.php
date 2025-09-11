<div x-data="{
    async deviceHash() {
        const txt = navigator.userAgent + '|' + (screen.width + 'x' + screen.height) + '|' + (navigator.platform || '');
        // Fallback si no hay crypto.subtle
        if (!('crypto' in window) || !('subtle' in crypto)) {
            // DJB2 simple
            let h = 5381;
            for (let i = 0; i < txt.length; i++) { h = ((h << 5) + h) + txt.charCodeAt(i); }
            return ('djb2_' + (h >>> 0).toString(16)).padStart(16, '0');
        }
        const enc = new TextEncoder().encode(txt);
        const buf = await crypto.subtle.digest('SHA-256', enc);
        return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
    }
}" x-init="deviceHash().then(h => $wire.device_hash = h).catch(() => {});">
   <div x-data="joinPanel()" x-init="init()" class="panel-hero d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                    <div class="card shadow-sm card-lift">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center mb-3">
                                <div class="brand-chip mr-2">
                                    <i class="fas fa-puzzle-piece"></i>
                                </div>
                                <div>
                                    <h2 class="h4 mb-0 font-weight-bold">PANEL</h2>
                                    <small class="text-muted">Juego educativo • Únete con tu código</small>
                                </div>

                                <!-- Estado Reverb/Echo (opcional, se oculta si no hay Echo) -->
                                <div class="ml-auto" x-show="hasEcho" x-cloak>
                                    <span class="badge" :class="stateClass()" x-text="stateLabel()"></span>
                                </div>
                            </div>

                            @if (session('error'))
                                <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                            @endif

                            <div class="form-group">
                                <label for="code" class="mb-1">Código de partida</label>
                                <div class="input-group input-group-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-gamepad text-primary"></i>
                                        </span>
                                    </div>
                                    <input
                                        id="code"
                                        type="text"
                                        class="form-control code-input"
                                        placeholder="ABC123"
                                        maxlength="6"
                                        pattern="[A-Za-z0-9]{6}"
                                        autocomplete="one-time-code"
                                        inputmode="latin"
                                        style="text-transform: uppercase;"
                                        wire:model.defer="code"
                                        x-on:input="formatCode($event)"
                                        x-on:keydown.enter.prevent="$wire.join()"
                                    >
                                </div>
                                @if ($error)
                                    <div class="text-danger mt-2">{{ $error }}</div>
                                @endif
                                <small class="form-text text-muted mt-2">
                                    Sugerencia: pega el código con <kbd>Ctrl</kbd>+<kbd>V</kbd> o presiona <kbd>Enter</kbd> para unirte.
                                </small>
                            </div>

                            <div class="d-flex align-items-center mt-3">
                                <button
                                    class="btn btn-primary btn-lg btn-block"
                                    wire:click="join"
                                    wire:loading.attr="disabled"
                                    wire:target="join"
                                >
                                    <span wire:loading.remove wire:target="join">
                                        <i class="fas fa-sign-in-alt mr-1"></i> Unirme
                                    </span>
                                    <span wire:loading wire:target="join">
                                        <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                        Conectando…
                                    </span>
                                </button>
                            </div>

                            <div class="mt-4">
                                <details>
                                    <summary class="text-muted">¿Problemas para entrar?</summary>
                                    <ul class="small pl-3 mt-2 mb-0 text-muted">
                                        <li>Verifica que el código tenga 6 caracteres (letras y números).</li>
                                        <li>Asegúrate de tener buena conexión a Internet.</li>
                                        <li>Si ves “Reconectando…”, espera unos segundos y reintenta.</li>
                                    </ul>
                                </details>
                            </div>
                        </div>

                        <div class="card-footer text-center py-2 small text-muted">
                            Hecho con ♥ para la educación — <b>PANEL</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>
   @push('js')
    <script>
        function joinPanel() {
            return {
                hasEcho: false,
                state: 'desconectado',

                async deviceHash() {
                    const txt = navigator.userAgent + '|' + (screen.width + 'x' + screen.height) + '|' + (navigator.platform || '');
                    if (!('crypto' in window) || !('subtle' in crypto)) {
                        let h = 5381;
                        for (let i = 0; i < txt.length; i++) { h = ((h << 5) + h) + txt.charCodeAt(i); }
                        return ('djb2_' + (h >>> 0).toString(16)).padStart(16, '0');
                    }
                    const enc = new TextEncoder().encode(txt);
                    const buf = await crypto.subtle.digest('SHA-256', enc);
                    return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
                },

                initEchoState() {
                    try {
                        const conn = window.Echo?.connector?.pusher?.connection;
                        if (!conn) return;
                        this.hasEcho = true;
                        this.state = conn.state || 'desconectado';
                        conn.bind('state_change', ({ previous, current }) => { this.state = current; });
                    } catch (e) { /* silencioso */ }
                },

                stateLabel() {
                    switch (this.state) {
                        case 'connected': return 'En línea';
                        case 'connecting': case 'initialized': return 'Conectando…';
                        case 'unavailable': case 'failed': case 'disconnected': default: return 'Sin conexión';
                    }
                },
                stateClass() {
                    switch (this.state) {
                        case 'connected': return 'badge-online';
                        case 'connecting': case 'initialized': return 'badge-pending';
                        default: return 'badge-offline';
                    }
                },

                formatCode(e) {
                    // Forzar mayúsculas y quitar espacios
                    const el = e.target;
                    let v = (el.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                    if (el.value !== v) el.value = v;
                },

                async init() {
                    // Hash de dispositivo -> Livewire
                    try {
                        const h = await this.deviceHash();
                        if (h) this.$wire.device_hash = h;
                    } catch (_) {}

                    // Estado Echo/Reverb (si existe)
                    this.initEchoState();
                }
            }
        }
    </script>
    @endpush

</div>
