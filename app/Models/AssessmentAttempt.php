<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'assessment_id',
        'user_id',
        'score',
        'max_score',
        'accuracy',
        'status',
        'answers',
        'started_at',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'answers' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answerRecords(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'assessment_attempt_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isTimedOut(): bool
    {
        $limit = $this->assessment?->time_limit_minutes;
        if (! $limit || ! $this->started_at) {
            return false;
        }

        return $this->started_at->copy()->addMinutes($limit)->isPast();
    }

    public function remainingSeconds(): ?int
    {
        $limit = $this->assessment?->time_limit_minutes;
        if (! $limit || ! $this->started_at) {
            return null;
        }

        $deadline = $this->started_at->copy()->addMinutes((int) $limit);

        return max(0, $deadline->getTimestamp() - now()->getTimestamp());
    }
}
