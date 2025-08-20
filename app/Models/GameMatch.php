<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $fillable = [
        'game_session_id',
        'player1_participant_id',
        'player2_participant_id',
        'status',
        'winner_participant_id',
        'starts_at',
        'ends_at',
        'meta'
    ];

    protected $casts = ['meta' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function p1()
    {
        return $this->belongsTo(SessionParticipant::class, 'player1_participant_id');
    }

    public function p2()
    {
        return $this->belongsTo(SessionParticipant::class, 'player2_participant_id');
    }

    public function winner()
    {
        return $this->belongsTo(SessionParticipant::class, 'winner_participant_id');
    }

    public function assignedQuestions()
    {
        return $this->hasMany(AssignedQuestion::class, 'game_match_id');
    }
}
