<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionParticipant extends Model
{
    protected $fillable = [
        'game_session_id',
        'user_id',
        'nickname',
        'score',
        'time_total_ms',
        'is_ignored',
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    protected $casts = [
        'is_ignored' => 'bool',
    ];
}
