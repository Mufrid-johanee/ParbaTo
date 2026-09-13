<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'course_id',
        'classroom_id',
        'mission_id',
        'created_by',
        'title',
        'description',
        'type',
        'instructions',
        'time_limit_minutes',
        'pass_score',
        'max_attempts',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id || $user->hasRole(User::ROLE_ADMIN);
    }

    public function isAvailableNow(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function maxPoints(): int
    {
        return (int) $this->questions->sum('points');
    }
}
