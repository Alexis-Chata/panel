{{-- Layout puente: AdminLTE + Livewire --}}
@extends('adminlte::page')

{{-- Pasa el $title de Livewire a AdminLTE --}}
@section('title', $title ?? config('app.name'))

{{-- (Opcional) encabezado de contenido con el mismo título --}}
@section('content_header')
    @isset($header)
    <div class="container">
        <div class="row">
            <div class="col-12">
                 <h1 class="mb-2">{{ $header }}</h1>
            </div>
        </div>
    </div>
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
    {{-- Código JS específico de esta vista Livewire --}}
@endsection

{{-- Reenvía stacks (si tu app los usa en vistas hijas) --}}
@push('js')
    @stack('js')
@endpush
@push('css')
    @stack('css')
@endpush
