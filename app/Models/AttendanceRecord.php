<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'class_session_id',
        'user_id',
        'method',
        'status',
        'marked_by',
        'ip_address',
        'checked_in_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
