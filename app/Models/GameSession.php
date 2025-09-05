<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'code', 'title', 'phase_mode', 'questions_total', 'timer_default',
        'student_view_mode', 'is_active', 'is_running', 'current_q_index',
        'is_paused', 'starts_at', 'current_q_started_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_running' => 'boolean',
        'is_paused' => 'boolean',
        'starts_at' => 'datetime',
        'current_q_started_at' => 'datetime',
    ];

    public function sessionQuestions()
    {
        return $this->hasMany(SessionQuestion::class);
    }

    public function participants()
    {
        return $this->hasMany(SessionParticipant::class);
    }
}
