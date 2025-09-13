<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PingTest implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function broadcastOn(): array
    {
        // canal público simple
        return ['public.ping'];
    }

    public function broadcastAs(): string
    {
        // alias del evento
        return 'pong';
    }

    public function broadcastWith(): array
    {
        return ['time' => now()->toIso8601String()];
    }
}
