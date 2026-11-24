<?php

namespace App\Http\Controllers;

use App\Exports\GameSessionAnalyticsExport;
use App\Exports\GameSessionResultsExport;
use App\Models\GameSession;
use App\Models\SessionParticipant;
use App\Support\SessionAnalytics;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SessionExportController extends Controller
{
    /** Permiso de exportar + acceso a ESA partida (dueño, Admin o partida sin dueño). */
    private function authorizeExport(Request $request, GameSession $gameSession): void
    {
        $user = $request->user();
        abort_unless($user->can('sessions.export') && $gameSession->isManageableBy($user), 403);
    }

    public function resultsPdf(Request $request, GameSession $gameSession)
    {
        $this->authorizeExport($request, $gameSession);

        $ranking = SessionParticipant::where('game_session_id', $gameSession->id)
            ->with('user:id,name,email')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->get();

        return Pdf::loadView('pdf/session-results', [
            'session' => $gameSession,
            'ranking' => $ranking,
        ])->setPaper('a4', 'portrait')
            ->download('resultados_'.$gameSession->code.'.pdf');
    }

    public function resultsExcel(Request $request, GameSession $gameSession)
    {
        $this->authorizeExport($request, $gameSession);

        return Excel::download(new GameSessionResultsExport($gameSession->id), 'resultados_'.$gameSession->code.'.xlsx');
    }

    public function analyticsPdf(Request $request, GameSession $gameSession)
    {
        $this->authorizeExport($request, $gameSession);

        return Pdf::loadView('pdf/session-analytics', [
            'session' => $gameSession,
            'rows' => SessionAnalytics::rows($gameSession)->all(),
        ])->setPaper('a4', 'portrait')
            ->download('analitico_'.$gameSession->code.'.pdf');
    }

    public function analyticsExcel(Request $request, GameSession $gameSession)
    {
        $this->authorizeExport($request, $gameSession);

        return Excel::download(new GameSessionAnalyticsExport($gameSession->id), 'analitico_'.$gameSession->code.'.xlsx');
    }
}
