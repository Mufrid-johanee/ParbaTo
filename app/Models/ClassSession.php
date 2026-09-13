<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClassSession extends Model
{
    protected $fillable = [
        'classroom_id',
        'started_by',
        'title',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'attendance_code',
        'attendance_code_expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'attendance_code_expires_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClassroomActivity::class);
    }

    public function helpRequests(): HasMany
    {
        return $this->hasMany(HelpRequest::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function rotateAttendanceCode(int $ttlMinutes = 15): string
    {
        $code = strtoupper(Str::random(8));
        $this->forceFill([
            'attendance_code' => hash('sha256', $code),
            'attendance_code_expires_at' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $code;
    }

    public function attendanceCodeMatches(string $plainCode): bool
    {
        if (! $this->attendance_code || ! $this->attendance_code_expires_at) {
            return false;
        }

        if ($this->attendance_code_expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->attendance_code, hash('sha256', strtoupper(trim($plainCode))));
    }
}
