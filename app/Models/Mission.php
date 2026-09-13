<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'slug',
        'description',
        'problem_statement',
        'objective',
        'difficulty',
        'estimated_minutes',
        'xp_reward',
        'mode',
        'status',
        'deadline_at',
        'assessment_criteria',
        'submission_requirements',
        'completion_criteria',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'assessment_criteria' => 'array',
            'submission_requirements' => 'array',
            'completion_criteria' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MissionTask::class)->orderBy('position');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(MissionResource::class)->orderBy('position');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
