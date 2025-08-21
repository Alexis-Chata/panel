<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionParticipant extends Model
{
    protected $fillable = [
        'game_session_id',
        'user_id',
        'nickname',
        'is_active',
        'joined_at',
        'phase1_score',
        'phase2_score',
        'phase3_score',
        'total_score',
        'meta'
    ];

    protected $casts = ['meta' => 'array', 'joined_at' => 'datetime'];

    protected static function booted(): void
    {
        static::created(function (self $p) {
            event(new \App\Events\ParticipantUpdated($p->game_session_id, $p->id));
        });

        static::updated(function (self $p) {
            event(new \App\Events\ParticipantUpdated($p->game_session_id, $p->id));
        });

        // (Opcional)
        // static::deleted(function (self $p) {
        //     event(new \App\Events\ParticipantUpdated($p->game_session_id, $p->id));
        // });
    }

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedQuestions()
    {
        return $this->hasMany(AssignedQuestion::class, 'participant_id');
    }
}
