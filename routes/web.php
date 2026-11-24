<?php

use App\Events\PingTest;
use App\Events\TestEvent;
use App\Http\Controllers\SessionExportController;
use App\Http\Controllers\UploadController;
use App\Livewire\Dashboard;
use App\Livewire\JoinSession;
use App\Livewire\ManageSessions;
use App\Livewire\PlayBasic;
use App\Livewire\QuestionBank;
use App\Livewire\RunSession;
use App\Livewire\ScreenDisplay;
use App\Livewire\WinnersView;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
})->name('home');

// Ruta de prueba de WebSockets: solo en entorno local (en producción cualquiera podía dispararla)
if (app()->environment('local')) {
    Route::get('/ping', function () {
        TestEvent::dispatch(['ok' => now()->toDateTimeString()]);
        event(new PingTest('hola desde /ping'));

        return 'ok';
    });
}

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('panel'))->name('dashboard');
});

Route::get('/', fn () => redirect()->route('panel'));

Route::middleware(['auth'])->group(function () {
    Route::get('/panel', Dashboard::class)->name('panel');

    // Docente/Admin
    Route::middleware(['role:Admin|Docente'])->group(function () {
        Route::get('/sessions', ManageSessions::class)->name('sessions.index');
        Route::get('/sessions/{gameSession}/run', RunSession::class)->name('sessions.run');

        // Pantalla proyectada: muestra la respuesta correcta, no debe abrirla un alumno
        Route::get('screen/{gameSession}', ScreenDisplay::class)->name('screen.display');

        // Exportaciones (además del rol, el controlador valida permiso y acceso a la partida)
        Route::controller(SessionExportController::class)->prefix('sessions/{gameSession}/export')->group(function () {
            Route::get('pdf', 'resultsPdf')->name('sessions.export.pdf');
            Route::get('excel', 'resultsExcel')->name('sessions.export.excel');
            Route::get('analytics-pdf', 'analyticsPdf')->name('sessions.export.analytics.pdf');
            Route::get('analytics-excel', 'analyticsExcel')->name('sessions.export.analytics.excel');
        });

        Route::get('/questions', QuestionBank::class)->name('questions.index');
        Route::redirect('/question-groups', '/questions')->name('question-groups.index');

        // Subida de imágenes de CKEditor (antes la podía usar cualquier usuario autenticado)
        Route::post('/ckeditor/upload', [UploadController::class, 'ckeditor'])
            ->middleware('throttle:30,1')
            ->name('ckeditor.upload');

        Route::get('/questions/template/csv', function () {
            $csv = "statement,feedback,A,B,C,D,correct\n";
            $csv .= "\"Capital de Perú\",\"Lima es la capital.\",\"Lima\",\"Cusco\",\"Arequipa\",\"Trujillo\",\"A\"\n";
            $csv .= "\"7x8\",\"7×8 = 56\",\"54\",\"56\",\"58\",\"60\",\"B\"\n";

            return Response::streamDownload(fn () => print ($csv), 'plantilla_preguntas.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        })->name('questions.template.csv');
    });

    // Estudiante
    Route::get('/join', JoinSession::class)->name('join');
    Route::get('/play/{gameSession}', PlayBasic::class)->name('play')->middleware('ensure.session.device');
    Route::get('/winners/{gameSession}', WinnersView::class)->name('winners');
    Route::get('/play/code/{code}', function (string $code) {
        // Enviamos al join con el code prellenado (se mostrará y podrás unirte)
        return redirect()->route('join', ['code' => strtoupper($code)]);
    })->name('play.bycode');
});
