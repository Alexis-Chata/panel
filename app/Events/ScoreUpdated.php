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

class ScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $participantId;
    public $scores;

    /**
     * Create a new event instance.
     */
    public function __construct(int $sessionId, int $participantId)
    {
        $this->sessionId = $sessionId;
        $this->participantId = $participantId;
        $this->scores = [
            'phase1' => null,
            'phase2' => null,
            'phase3' => null,
            'total' => null,
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
            new PrivateChannel('channel-name'),
        ];
    }

    public function broadcastWith()
    {
        $participant = \App\Models\SessionParticipant::find($this->participantId);
        return [
            'participant_id' => $this->participantId,
            'phase1_score'   => $participant->phase1_score,
            'phase2_score'   => $participant->phase2_score,
            'phase3_score'   => $participant->phase3_score,
            'total_score'    => $participant->total_score,
        ];
    }
}
