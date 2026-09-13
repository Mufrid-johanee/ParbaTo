<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionResource extends Model
{
    protected $fillable = [
        'mission_id',
        'title',
        'type',
        'url',
        'file_path',
        'position',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
