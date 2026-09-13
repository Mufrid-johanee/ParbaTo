<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkill extends Model
{
    protected $fillable = [
        'user_id',
        'skill_id',
        'mastery',
        'evidence_count',
        'last_assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_assessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
