<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['question_pool_id', 'code', 'type', 'stem', 'media', 'difficulty', 'meta', 'time_limit_seconds'];
    protected $casts = ['media' => 'array', 'meta' => 'array'];

    public function pool()
    {
        return $this->belongsTo(QuestionPool::class, 'question_pool_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
