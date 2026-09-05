<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoadTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'target_id', 'scan_run_id', 'endpoint', 'http_method',
        'request_body_template', 'virtual_users', 'concurrency', 'ramp_up_seconds',
        'duration_seconds', 'max_rps', 'max_total_requests', 'request_timeout_ms',
        'error_rate_threshold_percent', 'latency_threshold_ms',
        'explicitly_confirmed', 'confirmed_by', 'confirmed_at', 'stop_reason',
    ];

    protected function casts(): array
    {
        return [
            'explicitly_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
            'request_body_template' => 'encrypted',
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

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(LoadTestMetric::class);
    }
}
