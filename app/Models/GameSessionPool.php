<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSessionPool extends Model
{
    protected $fillable = ['game_session_id', 'question_pool_id', 'phase', 'weight'];

    public function session()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function pool()
    {
        return $this->belongsTo(QuestionPool::class);
    }
}
