<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_STUDENT = 'student';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
        'role',
        'avatar_url',
        'major',
        'bio',
        'firebase_uid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'firebase_uid',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
            'level' => 'integer',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function missionEnrollments(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function studentSkills(): HasMany
    {
        return $this->hasMany(StudentSkill::class);
    }

    public function classroomMemberships(): HasMany
    {
        return $this->hasMany(ClassroomMember::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['earned_at'])
            ->withTimestamps();
    }

    public function xpLedger(): HasMany
    {
        return $this->hasMany(XpLedger::class);
    }

    public function preferredName(): string
    {
        return $this->display_name ?: $this->name;
    }
}
