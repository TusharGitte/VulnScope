<?php

// Hard, platform-wide ceilings. Individual project scope_rules can set LOWER limits,
// never higher. ScopeRule::effectiveMax*() always takes the min() of the two.
return [
    'max_request_rate' => env('VAPT_MAX_REQUEST_RATE', 20),
    'max_concurrency' => env('VAPT_MAX_CONCURRENCY', 25),
    'max_duration_seconds' => env('VAPT_MAX_DURATION_SECONDS', 600),
    'max_total_requests' => env('VAPT_MAX_TOTAL_REQUESTS', 50000),
    'max_error_rate_percent' => env('VAPT_MAX_ERROR_RATE_PERCENT', 25),
    'max_latency_ms' => env('VAPT_MAX_LATENCY_MS', 10000),
    'max_crawl_pages' => env('VAPT_MAX_CRAWL_PAGES', 100),
];
