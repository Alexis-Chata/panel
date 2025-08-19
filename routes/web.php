<?php

use Illuminate\Support\Facades\Route;

if (app()->isLocal()) {
    Route::get('/ping', function () {
        event(new \App\Events\Ping('hola desde /ping'));
        return 'Ping emitido';
    });
}
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
