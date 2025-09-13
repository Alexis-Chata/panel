<?php

use App\Exports\GameSessionAnalyticsExport;
use App\Exports\GameSessionResultsExport;
use App\Http\Controllers\UploadController;
use App\Livewire\Dashboard;
use App\Livewire\JoinSession;
use App\Livewire\ManageQuestions;
use App\Livewire\ManageSessions;
use App\Livewire\PlayBasic;
use App\Livewire\PlayFullscreen;
use App\Livewire\RunSession;
use App\Livewire\ScreenDisplay;
use App\Livewire\WinnersView;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('panel');
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/', fn() => redirect()->route('panel'));

Route::middleware(['auth'])->group(function () {
    Route::get('/panel', Dashboard::class)->name('panel');
    Route::get('/sessions/{gameSession}/export/pdf', function (\App\Models\GameSession $gameSession) {
        abort_unless(auth()->user()->can('sessions.export'), 403);

        $ranking = SessionParticipant::where('game_session_id', $gameSession->id)
            ->with('user:id,name,email')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->get();

        $pdf = Pdf::loadView('pdf/session-results', [
            'session'  => $gameSession,
            'ranking'  => $ranking,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('resultados_' . $gameSession->code . '.pdf');
    })->name('sessions.export.pdf');
    // Excel analítico
    Route::get('/sessions/{gameSession}/export/analytics-excel', function (GameSession $gameSession) {
        abort_unless(auth()->user()->can('sessions.export'), 403);
        return Excel::download(
            new GameSessionAnalyticsExport($gameSession->id),
            'analitico_' . $gameSession->code . '.xlsx'
        );
    })->name('sessions.export.analytics.excel');

    // PDF analítico
    Route::get('/sessions/{gameSession}/export/analytics-pdf', function (GameSession $gameSession) {
        abort_unless(auth()->user()->can('sessions.export'), 403);

        $sqs = SessionQuestion::where('game_session_id', $gameSession->id)
            ->with('question.options')
            ->orderBy('q_order')->get();

        $rows = [];
        foreach ($sqs as $i => $sq) {
            $opts = $sq->question->options->sortBy('opt_order')->values();

            $labels = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            $isCorrect = [];
            foreach ($opts as $o) {
                $isCorrect[$o->label] = (bool) $o->is_correct;
            }

            $dist = Answer::selectRaw('question_option_id, COUNT(*) as c')
                ->where('session_question_id', $sq->id)
                ->whereNotNull('question_option_id')
                ->groupBy('question_option_id')
                ->pluck('c', 'question_option_id');

            foreach ($opts as $o) {
                $labels[$o->label] = (int) ($dist[$o->id] ?? 0);
            }

            $answered = Answer::where('session_question_id', $sq->id)->count();
            $corrects = Answer::where('session_question_id', $sq->id)->where('is_correct', true)->count();
            $acc = $answered ? ($corrects * 100 / $answered) : 0.0;

            $rows[] = [
                'n'        => $i + 1,
                'q'        => mb_strimwidth($sq->question->statement, 0, 140, '…', 'UTF-8'),
                'correct'  => array_search(true, $isCorrect, true) ?: '',
                'answered' => $answered,
                'corrects' => $corrects,
                'acc'      => $acc,
                'A'        => $labels['A'],
                'B'        => $labels['B'],
                'C'        => $labels['C'],
                'D'        => $labels['D'],
            ];
        }

        $pdf = Pdf::loadView('pdf/session-analytics', [
            'session' => $gameSession,
            'rows'    => $rows,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('analitico_' . $gameSession->code . '.pdf');
    })->name('sessions.export.analytics.pdf');

    Route::get('/sessions/{gameSession}/export/excel', function (GameSession $gameSession) {
        //$this->authorize('export', $gameSession); // opcional si creas Policy
        abort_unless(auth()->user()->can('sessions.export'), 403);

        $filename = 'resultados_' . $gameSession->code . '.xlsx';
        return Excel::download(new GameSessionResultsExport($gameSession->id), $filename);
    })->middleware(['auth'])->name('sessions.export.excel');

    Route::get('screen/{gameSession}', ScreenDisplay::class)->name('screen.display');

    // Docente/Admin
    Route::middleware(['role:Admin|Docente'])->group(function () {
        Route::get('/sessions', ManageSessions::class)->name('sessions.index');
        Route::get('/sessions/{gameSession}/run', RunSession::class)->name('sessions.run');
    });
    Route::get('/questions', ManageQuestions::class)
        ->name('questions.index')
        ->middleware('role:Admin|Docente');

    Route::post('/ckeditor/upload', [UploadController::class, 'ckeditor'])
    ->middleware(['auth']) // opcional
    ->name('ckeditor.upload');

    Route::get('/questions/template/csv', function () {
        $csv = "statement,feedback,A,B,C,D,correct\n";
        $csv .= "\"Capital de Perú\",\"Lima es la capital.\",\"Lima\",\"Cusco\",\"Arequipa\",\"Trujillo\",\"A\"\n";
        $csv .= "\"7x8\",\"7×8 = 56\",\"54\",\"56\",\"58\",\"60\",\"B\"\n";

        return Response::streamDownload(function () use ($csv) {
            echo $csv;
        }, 'plantilla_preguntas.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    })->middleware(['role:Admin|Docente'])->name('questions.template.csv');

    // Estudiante
    Route::get('/join', JoinSession::class)->name('join');
    Route::get('/play/{gameSession}', PlayBasic::class)->name('play')->middleware('ensure.session.device');
    Route::get('/winners/{gameSession}', WinnersView::class)->name('winners');
    Route::get('/play/code/{code}', function (string $code) {
        // Enviamos al join con el code prellenado (se mostrará y podrás unirte)
        return redirect()->route('join', ['code' => strtoupper($code)]);
    })->name('play.bycode');
});
