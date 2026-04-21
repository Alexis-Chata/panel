<div class="container py-4">
    <h4 class="mb-4">Ajustes del Sistema</h4>

    <form wire:submit="actualizar_ajustes" enctype="multipart/form-data">
        @csrf

        {{-- Nombre del sistema --}}
        <div class="mb-3">
            <label class="form-label">Nombre del sistema</label>
            <input type="text" class="form-control" wire:model.defer="ajustesistemaform.name" placeholder="Ej: Sistema de Ventas">
            @error('ajustesistemaform.name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        {{-- Logo actual --}}
        @if ($ajustesistemaform->configuracion?->logo)
            <div class="mb-3">
                <label class="form-label d-block">Logo actual</label>
                <img src="{{ asset($ajustesistemaform->configuracion->logo) }}" alt="Logo actual" style="max-height: 100px;">
            </div>
        @endif

        {{-- Subir nuevo logo --}}
        <div class="mb-3">
            <label class="form-label">Subir nuevo logo</label>
            <input type="file" class="form-control" wire:model="logo" accept="image/*">
            @error('logo') <small class="text-danger">{{ $message }}</small> @enderror

            <div wire:loading wire:target="logo" class="mt-2 text-primary">
                Subiendo imagen...
            </div>
        </div>
        {{-- Favicon actual --}}
        @if ($ajustesistemaform->configuracion?->favicon)
            <div class="mb-3">
                <label class="form-label d-block">Favicon actual</label>
                <img src="{{ asset($ajustesistemaform->configuracion->favicon) }}" alt="Logo actual" style="max-height: 100px;">
            </div>
        @endif
        {{-- Subir nuevo favicon --}}
        <div class="mb-3">
            <label class="form-label">Subir nuevo Favicon</label>
            <input type="file" class="form-control" wire:model="favicon" accept="ico/*">
            @error('favicon') <small class="text-danger">{{ $message }}</small> @enderror

            <div wire:loading wire:target="favicon" class="mt-2 text-primary">
                Subiendo imagen...
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            Guardar cambios
        </button>
    </form>
     @script
        <script>
            $wire.on('success', mensaje => {
                Swal.fire({
                    position: "center-center",
                    icon: "success",
                    title: mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Aceptar"
                });
            });

            $wire.on('error', mensaje => {
                Swal.fire({
                    position: "center-center",
                    icon: "error",
                    title: mensaje,
                    showConfirmButton: true,
                    confirmButtonText: "Aceptar"
                });
            });
        </script>
    @endscript
</div>
