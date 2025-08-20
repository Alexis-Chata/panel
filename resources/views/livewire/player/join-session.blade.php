<div class="container py-4">
    <div class="card">
        <div class="card-header">Unirse a una partida</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Código</label>
                <input type="text" class="form-control" wire:model.defer="code" placeholder="ABC123">
                @if($error)
                    <div class="text-danger mt-2">{{ $error }}</div>
                @endif
            </div>
            <button class="btn btn-primary" wire:click="join">Unirme</button>
        </div>
    </div>
</div>
