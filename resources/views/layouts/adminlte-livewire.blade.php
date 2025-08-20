{{-- Layout puente: AdminLTE + Livewire --}}
@extends('adminlte::page')

{{-- Pasa el $title de Livewire a AdminLTE --}}
@section('title', $title ?? config('app.name'))

{{-- (Opcional) encabezado de contenido con el mismo título --}}
@section('content_header')
    @isset($title)
        <h1 class="m-0">{{ $title }}</h1>
    @endisset
@endsection

{{-- Slot principal de Livewire --}}
@section('content')
    {{ $slot }}
@endsection

{{-- Reenvía stacks --}}
@push('js')   @stack('js')   @endpush
@push('css')  @stack('css')  @endpush
