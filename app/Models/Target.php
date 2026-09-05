<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'input_url', 'normalized_url', 'hostname', 'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function reconResults(): HasMany
    {
        return $this->hasMany(ReconResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(VulnerabilityFinding::class);
    }
}
