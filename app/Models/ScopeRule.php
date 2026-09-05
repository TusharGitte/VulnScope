<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'allowed_domains', 'allowed_ip_ranges', 'excluded_hosts',
        'allowed_ports', 'allowed_endpoints', 'window_start', 'window_end',
        'max_request_rate', 'max_concurrency', 'max_duration_seconds', 'max_total_requests',
        'authenticated_testing_allowed', 'authorization_notes', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'allowed_domains' => 'array',
            'allowed_ip_ranges' => 'array',
            'excluded_hosts' => 'array',
            'allowed_ports' => 'array',
            'allowed_endpoints' => 'array',
            'window_start' => 'datetime',
            'window_end' => 'datetime',
            'confirmed_at' => 'datetime',
            'authenticated_testing_allowed' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isWithinWindow(?\DateTimeInterface $at = null): bool
    {
        $at ??= now();

        return $at->greaterThanOrEqualTo($this->window_start) && $at->lessThanOrEqualTo($this->window_end);
    }

    /**
     * Effective ceiling = min(project-configured limit, platform-wide hard ceiling from config).
     * Always resolve limits through this method rather than reading the column directly.
     */
    public function effectiveMaxRequestRate(): int
    {
        return min($this->max_request_rate, (int) config('vapt.max_request_rate'));
    }

    public function effectiveMaxConcurrency(): int
    {
        return min($this->max_concurrency, (int) config('vapt.max_concurrency'));
    }

    public function effectiveMaxDurationSeconds(): int
    {
        return min($this->max_duration_seconds, (int) config('vapt.max_duration_seconds'));
    }

    public function effectiveMaxTotalRequests(): int
    {
        return min($this->max_total_requests, (int) config('vapt.max_total_requests'));
    }
}
