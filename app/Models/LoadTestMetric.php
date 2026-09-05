<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadTestMetric extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'load_test_id', 'sampled_at', 'requests_per_sec', 'throughput_bytes_per_sec',
        'p50_latency_ms', 'p95_latency_ms', 'p99_latency_ms', 'max_latency_ms',
        'error_percent', 'timeout_percent', 'status_code_distribution', 'concurrent_users',
    ];

    protected function casts(): array
    {
        return [
            'sampled_at' => 'datetime',
            'status_code_distribution' => 'array',
        ];
    }

    public function loadTest(): BelongsTo
    {
        return $this->belongsTo(LoadTest::class);
    }

    public function getRequestsSentAttribute(): int
    {
        return (int) ($this->status_code_distribution['total_sent'] ?? 0);
    }

    public function getSuccessfulRequestsAttribute(): int
    {
        $distribution = $this->status_code_distribution ?? [];
        $success = 0;
        foreach ($distribution as $code => $count) {
            if (is_numeric($code) && (int) $code >= 200 && (int) $code < 500) {
                $success += (int) $count;
            }
        }
        return $success;
    }

    public function getFailedRequestsAttribute(): int
    {
        return (int) ($this->status_code_distribution['errors'] ?? 0);
    }

    public function getAvgLatencyMsAttribute(): int
    {
        return (int) ($this->p50_latency_ms ?? 0);
    }

    public function getCircuitBreakerTrippedAttribute(): bool
    {
        return (bool) ($this->status_code_distribution['circuit_breaker_tripped'] ?? false);
    }

    public function getBytesReceivedAttribute(): int
    {
        return (int) ($this->status_code_distribution['bytes_received'] ?? (($this->throughput_bytes_per_sec ?? 0) * 5));
    }
}
