<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
