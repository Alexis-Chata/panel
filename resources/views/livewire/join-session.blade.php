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
    <div class="card">
        <div class="card-header">Unirse a una partida</div>
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="form-group">
                <label>Código</label>
                <input type="text" class="form-control" wire:model.defer="code" placeholder="ABC123">
                @if ($error)
                    <div class="text-danger mt-2">{{ $error }}</div>
                @endif
            </div>
            <button class="btn btn-primary" wire:click="join">Unirme</button>
        </div>
    </div>
</div>
