<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PingTest implements ShouldBroadcastNow
{
    use InteractsWithSockets, Dispatchable, SerializesModels;

    public string $msg;

    public function __construct(string $msg)
    {
        $this->msg = $msg;
    }

    public function broadcastOn(): array
    {
        // canal público simple
        return [new Channel('ping')];
    }

    public function broadcastAs(): string
    {
        // alias del evento
        return 'pong';
    }

    public function broadcastWith(): array
    {
        return [
            'msg'  => $this->msg,                 // 👈 incluye el mensaje
            'time' => now()->toIso8601String(),
        ];
    }
}
