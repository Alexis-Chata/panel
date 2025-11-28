<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'session_participant_id',
        'session_question_id',
        'question_option_id',
        'text',
        'is_correct',
        'time_ms',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function participant()
    {
        return $this->belongsTo(SessionParticipant::class, 'session_participant_id');
    }

    public function sessionQuestion()
    {
        return $this->belongsTo(SessionQuestion::class);
    }

    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
