<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pantalla</title>

    {{-- Bootstrap 4.6 (local). Si tu ruta difiere, usa el CDN comentado abajo. --}}
    {{-- <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}"> --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    {{-- Estilos propios mínimos --}}
    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
            background: #0b1020;
            color: #fff;
        }

        .screen {
            min-height: 100vh;
            width: 100vw;
        }

        .muted {
            opacity: .75;
        }

        .q-index {
            opacity: .65;
            letter-spacing: .06em;
        }

        .q-title {
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .2px;
            font-size: clamp(26px, 6vh, 64px);
        }

        .opt {
            border: 2px solid rgba(255, 255, 255, .18);
            border-radius: 18px;
            padding: clamp(12px, 2.2vh, 28px);
            background: rgba(255, 255, 255, .03);
        }

        .opt+.opt {
            margin-top: 1rem;
        }

        .opt-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: clamp(36px, 6vh, 56px);
            height: clamp(36px, 6vh, 56px);
            border-radius: 12px;
            font-weight: 800;
            background: rgba(255, 255, 255, .12);
            font-size: clamp(18px, 3vh, 28px);
        }

        .opt-text {
            font-size: clamp(18px, 4vh, 40px);
            line-height: 1.3;
        }

        .opt-correct {
            border-color: rgba(0, 255, 143, .65);
            background: rgba(0, 255, 143, .08);
            box-shadow: 0 0 0 2px rgba(0, 255, 143, .15) inset;
        }

        .badge-lg {
            font-size: clamp(12px, 2.2vh, 20px);
            padding: .5em .8em;
        }

    .ck-content figure.media { display:block; max-width:50%; margin:1rem 0; }
    .ck-content figure.media > div { position:relative !important; width:100% !important; padding-bottom:56.25% !important; height:0 !important; }
    .ck-content figure.media iframe { position:absolute !important; top:0; left:0; width:100% !important; height:100% !important; display:block !important; }
    /* Por si el editor guardó solo <oembed>, lo transformaremos con JS (abajo). */
        .q-title {
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: .1px;
        /* tamaño más moderado */
        font-size: clamp(22px, 4.2vh, 44px);
        }

        /* Centrar texto y elementos del enunciado */
        .q-title .ck-content { text-align: center; }

        /* Si el enunciado trae párrafos/títulos muy grandes, suavízalos */
        .q-title .ck-content h1 { font-size: 1.6em; }
        .q-title .ck-content h2 { font-size: 1.4em; }
        .q-title .ck-content p  { font-size: 1em; margin-bottom: .6rem; }

        /* Centrar y hacer responsive el video/imágenes embebidas */
        .ck-content figure.media {
        display:block;
        max-width: 72%;            /* antes tenías 50%, se veía angosto */
        margin: 1rem auto;         /* centra el figure */
        }
        @media (max-width: 992px){
        .ck-content figure.media { max-width: 100%; }
        }

        /* Mantén el hack de alto para YouTube */
        .ck-content figure.media > div {
        position:relative !important;
        width:100% !important;
        padding-bottom:56.25% !important;
        height:0 !important;
        }
        .ck-content figure.media iframe {
        position:absolute !important;
        top:0; left:0; width:100% !important; height:100% !important;
        display:block !important;
        }

    </style>

    @vite(['resources/js/app.js']) {{-- Echo/Reverb + axios --}}
    @livewireStyles
</head>

<body>
    {{ $slot }}

    {{-- Bootstrap bundle (si usas algún JS de BS). No interfiere con Livewire. --}}
    {{-- <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts
    @stack('js')
</body>

</html>
