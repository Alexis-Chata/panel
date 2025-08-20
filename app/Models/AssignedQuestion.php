<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedQuestion extends Model
{
    protected $fillable = [
        'game_session_id',
        'participant_id',
        'question_id',
        'game_match_id',
        'phase',
        'order',
        'available_at',
        'expires_at',
        'meta'
    ];

    protected $casts = ['meta' => 'array', 'available_at' => 'datetime', 'expires_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function participant()
    {
        return $this->belongsTo(SessionParticipant::class, 'participant_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'game_match_id');
    }

    public function answer()
    {
        return $this->hasOne(Answer::class);
    }
}
