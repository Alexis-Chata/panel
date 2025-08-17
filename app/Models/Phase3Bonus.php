<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase3Bonus extends Model
{
    protected $fillable = ['game_session_id', 'question_id', 'participant_id', 'rank', 'points'];

    public function session()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function participant()
    {
        return $this->belongsTo(SessionParticipant::class);
    }
}
