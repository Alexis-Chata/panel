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

// === Jugador ===
Route::middleware(['auth'])->group(function () {
    Route::get('/join', \App\Livewire\Player\JoinSession::class)->name('player.join');
    Route::get('/play/{session}', \App\Livewire\Player\PlaySession::class)->name('player.play');
});

// === Admin ===
Route::middleware(['auth'/*, 'can:manage-sessions'*/])->group(function () {
    Route::get('/admin/sessions', \App\Livewire\Admin\SessionsIndex::class)->name('admin.sessions.index');
    Route::get('/admin/sessions/{session}', \App\Livewire\Admin\SessionLobby::class)->name('admin.sessions.lobby');
});

// Pizarra pública (solo lectura)
// Route::get('/board/{session}', \App\Livewire\Board\Leaderboard::class)->name('board.show');

// Board privada (requiere login)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/board/{session}', \App\Livewire\Board\Leaderboard::class)->name('board.show');
});

Route::middleware(['auth']) // ajusta a tus middlewares
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/question-builder', \App\Livewire\Admin\QuestionBuilder::class)->name('question-builder');
    });
