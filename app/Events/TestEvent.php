<?php

// app/Events/TestEvent.php
// app/Events/TestEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;      // ← IMPORTANTE
use Illuminate\Broadcasting\InteractsWithSockets;    // opcional
use Illuminate\Queue\SerializesModels;               // opcional

class TestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels; // ← IMPORTANTE

    public function __construct(public array $data) {}

    public function broadcastOn(): Channel
    {
        return new Channel('debug');
    }

    public function broadcastAs(): string
    {
        return 'DebugPing';
    }
}
