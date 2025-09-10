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

class ParticipantJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $sessionId,
        public readonly array $participant, // id, user_id, nickname, score, etc.
        public readonly int $total
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Presence channel por sesión/partida
        return [
            new PresenceChannel("game-session.{$this->sessionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'participant' => $this->participant,
            'total'       => $this->total,
            'joined_at'   => now()->toISOString(),
        ];
    }
}
