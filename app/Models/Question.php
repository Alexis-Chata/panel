<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['statement', 'feedback'];

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
