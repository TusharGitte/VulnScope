<?php

namespace Tests\Unit;

use App\Models\ScopeRule;
use Tests\TestCase;

class ScopeRuleTest extends TestCase
{
    public function test_effective_limits_never_exceed_platform_limits(): void
    {
        config(['vapt.max_request_rate' => 20, 'vapt.max_concurrency' => 25, 'vapt.max_duration_seconds' => 600, 'vapt.max_total_requests' => 50000]);
        $rule = new ScopeRule([
            'max_request_rate' => 100, 'max_concurrency' => 100, 'max_duration_seconds' => 3600, 'max_total_requests' => 1000000,
        ]);
        self::assertSame(20, $rule->effectiveMaxRequestRate());
        self::assertSame(25, $rule->effectiveMaxConcurrency());
        self::assertSame(600, $rule->effectiveMaxDurationSeconds());
        self::assertSame(50000, $rule->effectiveMaxTotalRequests());
    }
}
