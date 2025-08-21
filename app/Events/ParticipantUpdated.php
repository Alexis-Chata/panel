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

class ParticipantUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $sessionId,
        public int $participantId,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Reutilizamos el canal privado de scores para no abrir nuevos
        return [
            new PrivateChannel("sessions.{$this->sessionId}.scores"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ParticipantUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'     => $this->sessionId,
            'participant_id' => $this->participantId,
        ];
    }
}
