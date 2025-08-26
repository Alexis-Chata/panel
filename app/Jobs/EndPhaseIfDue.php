<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Services\PhaseOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EndPhaseIfDue implements ShouldQueue
{
    use Queueable;

    public int $sessionId;
    public string $expectedStatus; // 'phase1' | 'phase2' | 'phase3'

    /**
     * Create a new job instance.
     */
    public function __construct(int $sessionId, string $expectedStatus)
    {
        $this->sessionId = $sessionId;
        $this->expectedStatus = $expectedStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var GameSession|null $s */
        $s = GameSession::find($this->sessionId);
        if (!$s) return;

        // Si alguien cambió la fase manualmente, no hacemos nada
        if ($s->status !== $this->expectedStatus) return;

        // Si aún no vence, no hacemos nada
        if (!$s->phase_ends_at || now()->lt($s->phase_ends_at)) return;

        $svc = app(PhaseOrchestrator::class);

        switch ($this->expectedStatus) {
            case 'phase1':
                $svc->startPhase2($s);
                break;
            case 'phase2':
                $svc->startPhase3($s);
                break;
            case 'phase3':
                // Pasar a resultados
                $s->update(['status' => 'results', 'current_phase' => 0, 'phase_ends_at' => null]);
                event(new \App\Events\SessionPhaseChanged($s));
                break;
        }
    }
}
