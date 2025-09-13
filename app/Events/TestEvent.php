<?php

// app/Events/TestEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TestEvent implements ShouldBroadcastNow
{
    public function __construct(public array $data) {}
    public function broadcastOn(): Channel { return new Channel('debug'); } // público
    public function broadcastAs(): string { return 'DebugPing'; }
}
