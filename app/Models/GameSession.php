<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'code',
        'title',
        'status',
        'current_phase',
        'phase1_count',
        'phase2_count',
        'phase3_count',
        'settings_json',
        'starts_at',
        'ends_at'
    ];

    protected $casts = ['settings_json' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function participants()
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function assignedQuestions()
    {
        return $this->hasMany(AssignedQuestion::class);
    }

    public function phase3Bonuses()
    {
        return $this->hasMany(Phase3Bonus::class);
    }

    public function pools()
    {
        return $this->hasMany(GameSessionPool::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['lobby', 'phase1', 'phase2', 'phase3']);
    }
}
