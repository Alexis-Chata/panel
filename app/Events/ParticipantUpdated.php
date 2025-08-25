<?php

namespace App\Events;

use App\Models\SessionParticipant;
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

    public int $session_id;
    public int $participant_id;
    public string $action; // 'joined' | 'left' | 'updated'
    public array $participant;

    /**
     * Create a new event instance.
     */
    public function __construct(SessionParticipant $participant, string $action = 'joined')
    {
        $this->session_id     = $participant->game_session_id;
        $this->participant_id = $participant->id;
        $this->action         = $action;

        $this->participant = [
            'id'          => $participant->id,
            'user_id'     => $participant->user_id,
            'nickname'    => $participant->nickname,
            'total_score' => $participant->total_score,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("sessions.{$this->session_id}.participants"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ParticipantUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'     => $this->session_id,
            'participant_id' => $this->participant_id,
        ];
    }
}
