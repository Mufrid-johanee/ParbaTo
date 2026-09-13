<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PortfolioItem extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'title',
        'summary',
        'evidence',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
