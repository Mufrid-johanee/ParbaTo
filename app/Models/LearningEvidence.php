<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LearningEvidence extends Model
{
    protected $table = 'learning_evidences';

    protected $fillable = [
        'user_id',
        'skill_id',
        'source_type',
        'source_id',
        'evidence_type',
        'score',
        'weight',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'meta' => 'array',
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

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
