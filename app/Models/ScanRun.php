<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'target_id', 'stage', 'status', 'progress_percent',
        'started_by', 'started_at', 'finished_at', 'error_message', 'config',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'config' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function reconResults(): HasMany
    {
        return $this->hasMany(ReconResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(VulnerabilityFinding::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    /**
     * True when this run has been sitting queued/running for suspiciously long
     * without progress — almost always means no queue worker is consuming the
     * `vapt` queue (see run-worker.sh / README "Running the application").
     * Surfaced in the UI so a stuck job doesn't just look like "nothing happened".
     */
    public function isStalled(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $reference = $this->status === 'queued' ? $this->created_at : ($this->started_at ?? $this->created_at);

        return $reference !== null && $reference->diffInSeconds(now()) > 90;
    }
}
