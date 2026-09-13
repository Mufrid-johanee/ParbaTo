<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'domain',
    ];

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class);
    }

    public function studentSkills(): HasMany
    {
        return $this->hasMany(StudentSkill::class);
    }
}
