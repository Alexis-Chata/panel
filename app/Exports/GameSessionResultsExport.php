<?php

namespace App\Exports;

use App\Models\SessionParticipant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GameSessionResultsExport implements FromCollection, WithHeadings
{
    public function __construct(protected int $gameSessionId) {}

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return SessionParticipant::where('game_session_id', $this->gameSessionId)
            ->with('user:id,name,email')
            ->orderByDesc('score')
            ->orderBy('time_total_ms')
            ->get()
            ->map(function ($p) {
                return [
                    'Participante' => $p->nickname ?? ($p->user?->name ?: 'N/A'),
                    'Email'        => $p->user?->email,
                    'Puntaje'      => $p->score,
                    'Tiempo (s)'   => round($p->time_total_ms / 1000, 2),
                ];
            });
    }

    public function headings(): array
    {
        return ['Participante','Email','Puntaje','Tiempo (s)'];
    }
}
