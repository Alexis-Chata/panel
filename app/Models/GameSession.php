<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'user_id',
    ];

    protected $casts = [
        'questions_total' => 'integer',
        'timer_default' => 'integer',
        'current_q_index' => 'integer',
        'is_active' => 'boolean',
        'is_running' => 'boolean',
        'is_paused' => 'boolean',
        'current_q_started_at' => 'datetime',
        'starts_at' => 'datetime',
        'question_group_id' => 'integer',
        'user_id' => 'integer',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Partidas que el usuario puede gestionar/ejecutar: Admin todas; Docente las suyas y las sin dueño. */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Admin')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereNull('user_id')
            ->orWhere('user_id', $user->id));
    }

    public function isManageableBy(?User $user): bool
    {
        if (! $user || ! $user->hasAnyRole(['Admin', 'Docente'])) {
            return false;
        }

        return $user->hasRole('Admin')
            || $this->user_id === null
            || (int) $this->user_id === (int) $user->id;
    }

    /** Snapshot del estado que se envía por WebSocket a docente, alumnos y pantalla. */
    public function stateSnapshot(?int $duration = null): array
    {
        $duration ??= (int) ($this->sessionQuestions()
            ->where('q_order', $this->current_q_index)
            ->value('timer_override') ?? $this->timer_default);

        return [
            'is_active' => $this->is_active,
            'is_running' => $this->is_running,
            'is_paused' => $this->is_paused,
            'current_q_index' => $this->current_q_index,
            'current_q_started_at' => $this->current_q_started_at?->toIso8601String(),
            'questions_total' => $this->questions_total,
            'duration' => $duration,
        ];
    }

    /** Emite el estado actual. Un fallo de Reverb no debe romper la acción que ya se guardó. */
    public function broadcastState(?int $duration = null): void
    {
        rescue(fn () => \App\Events\GameSessionStateChanged::dispatch($this->id, $this->stateSnapshot($duration)));
    }

    /** Docente con acceso o alumno inscrito en la partida. */
    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isManageableBy($user)
            || $this->participants()->where('user_id', $user->id)->exists();
    }
}
