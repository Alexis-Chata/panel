<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'code',
        'title',
        'phase_mode',
        'questions_total',
        'timer_default',
        'student_view_mode',
        'is_active',
        'is_running',
        'current_q_index',
        'is_paused',
        'starts_at',
        'current_q_started_at',
        'question_group_id',
    ];

    protected $casts = [
        'questions_total'        => 'integer',
        'timer_default'          => 'integer',
        'current_q_index'        => 'integer',
        'is_active'              => 'boolean',
        'is_running'             => 'boolean',
        'is_paused'              => 'boolean',
        'current_q_started_at'   => 'datetime',
        'starts_at'              => 'datetime',
        'question_group_id'      => 'integer',
    ];

    public function sessionQuestions()
    {
        return $this->hasMany(SessionQuestion::class);
    }

    public function participants()
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class);
    }

    public function questionGroup()
    {
        return $this->belongsTo(QuestionGroup::class);
    }
}
