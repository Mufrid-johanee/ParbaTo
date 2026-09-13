<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionTaskProgress extends Model
{
    protected $table = 'mission_task_progress';

    protected $fillable = [
        'mission_enrollment_id',
        'mission_task_id',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class, 'mission_enrollment_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(MissionTask::class, 'mission_task_id');
    }
}
