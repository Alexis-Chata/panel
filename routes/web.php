<?php

use App\Livewire\Dashboard;
use App\Livewire\JoinSession;
use App\Livewire\ManageQuestions;
use App\Livewire\ManageSessions;
use App\Livewire\PlayBasic;
use App\Livewire\RunSession;
use App\Livewire\WinnersView;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/', fn() => redirect()->route('panel'));

Route::middleware(['auth'])->group(function () {
    Route::get('/panel', Dashboard::class)->name('panel');

    // Docente/Admin
    Route::middleware(['role:Admin|Docente'])->group(function () {
        Route::get('/sessions', ManageSessions::class)->name('sessions.index');
        Route::get('/sessions/{gameSession}/run', RunSession::class)->name('sessions.run');
    });
    Route::get('/questions', ManageQuestions::class)
        ->name('questions.index')
        ->middleware('role:Admin|Docente');

    // Estudiante
    Route::get('/join', JoinSession::class)->name('join');
    Route::get('/play/{gameSession}', PlayBasic::class)->name('play')->middleware('ensure.session.device');
    Route::get('/winners/{gameSession}', WinnersView::class)->name('winners');
    Route::get('/play/code/{code}', function (string $code) {
        // Enviamos al join con el code prellenado (se mostrará y podrás unirte)
        return redirect()->route('join', ['code' => strtoupper($code)]);
    })->name('play.bycode');
});
