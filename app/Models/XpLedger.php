<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpLedger extends Model
{
    protected $table = 'xp_ledger';

    protected $fillable = [
        'user_id',
        'amount',
        'source_type',
        'source_id',
        'description',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
