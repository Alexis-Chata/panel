<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PingTest implements ShouldBroadcastNow
{
    public function broadcastOn()  { return ['public.ping']; }
    public function broadcastAs()  { return 'pong'; } // si escuchas con .pong en el cliente
    public function broadcastWith(){ return ['time' => now()->toIso8601String()]; }
}
