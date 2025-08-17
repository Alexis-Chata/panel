<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionPool extends Model
{
    protected $fillable = ['name', 'slug', 'intended_phase', 'meta'];
    protected $casts = ['meta' => 'array'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
