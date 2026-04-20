<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class QuestionGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'parent_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Banco institucional (sin dueño explícito). */
    public function isInstitutional(): bool
    {
        return $this->user_id === null;
    }

    /** Categorías visibles para docente/admin en el banco / partidas. */
    public function scopeAccessibleFor(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('user_id')
            ->orWhere('user_id', $user->id));
    }

    public function isAccessibleBy(?User $user): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $this->user_id === null || (int) $this->user_id === (int) $user->id;
    }

    /** Si el usuario puede colgar una subcategoría de este padre. */
    public function canNestChildFor(?User $user): bool
    {
        $user ??= Auth::user();
        if (! $user || ! $this->isAccessibleBy($user)) {
            return false;
        }

        return $user->hasRole('Admin')
            ? true
            : ((! $this->isInstitutional()) && (int) $this->user_id === (int) $user->id);
    }
}
