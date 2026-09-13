<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'name',
        'room_label',
        'capacity',
        'rows',
        'cols',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClassroomMember::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function liveSession(): ?ClassSession
    {
        return $this->sessions()->where('status', 'live')->latest('started_at')->first();
    }
}
