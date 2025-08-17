<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'assigned_question_id',
        'question_option_id',
        'free_text',
        'is_correct',
        'response_ms',
        'answered_at',
        'meta'
    ];

    protected $casts = ['meta' => 'array', 'answered_at' => 'datetime'];

    public function assignment()
    {
        return $this->belongsTo(AssignedQuestion::class, 'assigned_question_id');
    }

    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
