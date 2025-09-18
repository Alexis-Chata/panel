<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameCountdownStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $seconds = 3,
        public string $phase = 'start', // start | advance
        public int $targetIndex = 0
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel("session.{$this->sessionId}");
    }

    public function broadcastAs()
    {
        return 'GameCountdownStarted';
    }

    public function broadcastWith()
    {
        return [
            'seconds'     => $this->seconds,
            'phase'       => $this->phase,
            'targetIndex' => $this->targetIndex,
        ];
    }
}
