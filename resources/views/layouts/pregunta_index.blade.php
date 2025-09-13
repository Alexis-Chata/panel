{{-- Layout puente: AdminLTE + Livewire --}}
@extends('adminlte::page')

{{-- Pasa el $title de Livewire a AdminLTE --}}
@section('title', $title ?? config('app.name'))

{{-- (Opcional) encabezado de contenido con el mismo título --}}
@section('content_header')
    @isset($header)
        <h1 class="mb-2">{{ $header }}</h1>
    @endisset
@endsection

@section('classes_body', 'dark-mode')

{{-- Slot principal de Livewire --}}
@section('content')
    {{ $slot }}
@endsection

{{-- Carga de activos Vite (solo aquí para evitar duplicados) --}}
@section('css')
    @vite(['resources/css/custom.css'])
@endsection

@section('css_pre')
    @vite(['resources/js/app.js']){{-- se agrego por el echo --}}
@endsection

@section('js')
    {{-- CKEditor 5 (super-build) --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
     @stack('js')
@endsection


@push('css')
    @stack('css')
@endpush
