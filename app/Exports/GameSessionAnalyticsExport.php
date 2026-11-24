<?php

namespace App\Exports;

use App\Support\SessionAnalytics;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GameSessionAnalyticsExport implements FromCollection, WithHeadings
{
    public function __construct(protected int $gameSessionId) {}

    public function collection(): Collection
    {
        return SessionAnalytics::rows($this->gameSessionId, 120)->map(fn (array $r) => [
            'N°' => $r['n'],
            'Pregunta' => $r['q'],
            'Correcta' => $r['correct'],
            'Respondidos' => $r['answered'],
            'Correctos' => $r['corrects'],
            '% Acierto' => $r['acc'],
            'A' => $r['A'],
            'B' => $r['B'],
            'C' => $r['C'],
            'D' => $r['D'],
        ]);
    }

    public function headings(): array
    {
        return ['N°', 'Pregunta', 'Correcta', 'Respondidos', 'Correctos', '% Acierto', 'A', 'B', 'C', 'D'];
    }
}
