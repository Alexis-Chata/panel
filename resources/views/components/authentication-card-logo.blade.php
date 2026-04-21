@php
    $configuracion = \App\Models\Configuracion::first();
@endphp
<a href="/">
    @if($configuracion && $configuracion->logo)
        <img src="{{ asset($configuracion->logo) }}" alt="Logo" width="128px" height="128px">
    @else
        <img src="{{ asset('imagenes/logo.png') }}" alt="Logo por defecto" width="128px" height="128px">
    @endif
</a>
