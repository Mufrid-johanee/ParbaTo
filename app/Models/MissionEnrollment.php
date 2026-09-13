<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MissionEnrollment extends Model
{
    protected $fillable = [
        'mission_id',
        'user_id',
        'status',
        'lifecycle_phase',
        'progress_percent',
        'started_at',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskProgress(): HasMany
    {
        return $this->hasMany(MissionTaskProgress::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(MissionSubmission::class)->latestOfMany();
    }
}
