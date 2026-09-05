<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoadTest;
use App\Models\LoadTestMetric;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Target;
use GuzzleHttp\Promise\Utils;
use Throwable;

class ControlledLoadTestService
{
    public function __construct(
        private ScopeEnforcementService $scope,
        private HttpRequestService $http,
    ) {
    }

    public function run(LoadTest $loadTest): void
    {
        $project = $loadTest->project()->firstOrFail();
        $target = $loadTest->target()->firstOrFail();
        $run = $loadTest->scanRun()->firstOrFail();

        $run->refresh();
        if (in_array($run->status, ['cancelled', 'interrupted'], true)) {
            return;
        }
        $run->update(['status' => 'running', 'progress_percent' => 1, 'started_at' => $run->started_at ?? now()]);
        $started = microtime(true);
        $totalSent = 0;
        $latencies = [];
        $totalErrors = 0;
        $totalTimeouts = 0;
        $stopReason = 'completed';

        try {
            $scope = $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $loadTest->endpoint);
            $maxRps = min((int) $loadTest->max_rps, $scope->effectiveMaxRequestRate(), (int) config('vapt.max_request_rate'));
            $maxConcurrency = min((int) $loadTest->concurrency, $scope->effectiveMaxConcurrency(), (int) config('vapt.max_concurrency'));
            $maxRequests = min((int) $loadTest->max_total_requests, $scope->effectiveMaxTotalRequests(), (int) config('vapt.max_total_requests'));
            $duration = min((int) $loadTest->duration_seconds, $scope->effectiveMaxDurationSeconds(), (int) config('vapt.max_duration_seconds'));
            $batchSize = max(1, min($maxConcurrency, $maxRps));

            while ($totalSent < $maxRequests && (microtime(true) - $started) < $duration) {
                $run->refresh();
                if ($run->status === 'interrupted' || $run->status === 'cancelled') {
                    $stopReason = 'manual_stop';
                    break;
                }

                $elapsed = microtime(true) - $started;
                $allowedConcurrency = $batchSize;
                if ($loadTest->ramp_up_seconds > 0 && $elapsed < $loadTest->ramp_up_seconds) {
                    $allowedConcurrency = max(1, min($batchSize, (int) ceil($batchSize * ($elapsed / $loadTest->ramp_up_seconds))));
                }
                $batchSizeNow = min($allowedConcurrency, $maxRequests - $totalSent);

                $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $loadTest->endpoint);
                $batchStartedAt = microtime(true);
                $promises = [];
                $requestStarted = [];
                $nextRequestAt = $nextRequestAt ?? microtime(true);
                for ($i = 0; $i < $batchSizeNow; $i++) {
                    $run->refresh();
                    if (in_array($run->status, ['cancelled', 'interrupted'], true)) {
                        $stopReason = 'manual_stop';
                        break;
                    }
                    $now = microtime(true);
                    if ($nextRequestAt > $now) {
                        usleep((int) round(($nextRequestAt - $now) * 1000000));
                    }
                    if ((microtime(true) - $started) >= $duration) break;

                    $requestStarted[$i] = microtime(true);
                    $options = [
                        'timeout' => max(0.1, ((int) $loadTest->request_timeout_ms) / 1000),
                        'stream' => true,
                        'headers' => ['Range' => 'bytes=0-65535'],
                    ];
                    if ($loadTest->request_body_template !== null && $loadTest->request_body_template !== '') {
                        $options['body'] = $loadTest->request_body_template;
                        $options['headers']['Content-Type'] = 'application/json';
                    }
                    $promises[$i] = $this->http->requestAsync($project, $loadTest->http_method ?: 'GET', $loadTest->endpoint, $options);
                    $nextRequestAt = max($nextRequestAt + (1 / max(1, $maxRps)), microtime(true));
                }

                if ($stopReason !== 'completed' && $promises === []) break;
                $settled = Utils::settle($promises)->wait();
                $batchLatencies = [];
                $statusCounts = [];
                $batchErrors = 0;
                $batchTimeouts = 0;
                $bytes = 0;

                foreach ($settled as $i => $result) {
                    $latency = (int) round((microtime(true) - ($requestStarted[$i] ?? microtime(true))) * 1000);
                    $batchLatencies[] = $latency;
                    $latencies[] = $latency;
                    if ($result['state'] === 'fulfilled') {
                        $response = $result['value'];
                        $code = (int) $response->getStatusCode();
                        $statusCounts[(string) $code] = ($statusCounts[(string) $code] ?? 0) + 1;
                        $length = $response->getHeaderLine('Content-Length');
                        if (is_numeric($length)) $bytes += (int) $length;
                        $body = $response->getBody();
                        if (method_exists($body, 'read')) $body->read(65536);
                        if (method_exists($body, 'close')) $body->close();
                        if ($code >= 400) $batchErrors++;
                    } else {
                        $batchErrors++;
                        $reason = strtolower($result['reason'] instanceof Throwable ? $result['reason']->getMessage() : (string) $result['reason']);
                        if (str_contains($reason, 'timeout')) $batchTimeouts++;
                        $statusCounts['error'] = ($statusCounts['error'] ?? 0) + 1;
                    }
                }

                $batchSent = count($settled);
                $totalSent += $batchSent;
                $totalErrors += $batchErrors;
                $totalTimeouts += $batchTimeouts;
                $currentErrorRate = $totalSent > 0 ? ($totalErrors / $totalSent) * 100 : 0;
                $p50 = $this->percentile($batchLatencies, 50);
                $p95 = $this->percentile($batchLatencies, 95);
                $p99 = $this->percentile($batchLatencies, 99);
                $maxLatency = $batchLatencies ? max($batchLatencies) : 0;

                $statusCounts['total_sent'] = $totalSent;
                $statusCounts['errors'] = $batchErrors;
                $statusCounts['bytes_received'] = $bytes;
                $statusCounts['circuit_breaker_tripped'] = false;

                if ($currentErrorRate > min((int) $loadTest->error_rate_threshold_percent, (int) config('vapt.max_error_rate_percent')) && $totalSent >= 5) {
                    $stopReason = 'error_threshold_breach';
                    $statusCounts['circuit_breaker_tripped'] = true;
                } elseif ($p95 > min((int) $loadTest->latency_threshold_ms, (int) config('vapt.max_latency_ms'))) {
                    $stopReason = 'latency_threshold_breach';
                    $statusCounts['circuit_breaker_tripped'] = true;
                }

                $elapsedNow = microtime(true) - $started;
                LoadTestMetric::create([
                    'load_test_id' => $loadTest->id,
                    'sampled_at' => now(),
                    'requests_per_sec' => (int) round($batchSent / max(0.001, microtime(true) - $batchStartedAt)),
                    'throughput_bytes_per_sec' => (int) round($bytes / max(0.001, microtime(true) - $batchStartedAt)),
                    'p50_latency_ms' => $p50,
                    'p95_latency_ms' => $p95,
                    'p99_latency_ms' => $p99,
                    'max_latency_ms' => $maxLatency,
                    'error_percent' => (float) (($batchSent > 0) ? (($batchErrors / $batchSent) * 100) : 0),
                    'timeout_percent' => (float) (($batchSent > 0) ? (($batchTimeouts / $batchSent) * 100) : 0),
                    'status_code_distribution' => $statusCounts,
                    'concurrent_users' => count($settled),
                ]);

                $run->progress_percent = min(99, (int) floor(($elapsedNow / max(1, $duration)) * 100));
                $run->save();

                if ($stopReason !== 'completed') break;
                if ($totalSent >= $maxRequests) {
                    $stopReason = 'total_requests_reached';
                    break;
                }

            }

            if ($stopReason === 'completed' && (microtime(true) - $started) >= $duration) {
                $stopReason = 'duration_reached';
            }

            $run->refresh();
            if (in_array($run->status, ['interrupted', 'cancelled'], true)) {
                $interrupted = true;
                $stopReason = $run->status === 'cancelled' ? 'cancelled' : 'manual_stop';
            } else {
                $interrupted = in_array($stopReason, ['manual_stop', 'error_threshold_breach', 'latency_threshold_breach'], true);
                $run->update([
                    'status' => $interrupted ? 'interrupted' : 'completed',
                    'progress_percent' => 100,
                    'finished_at' => now(),
                ]);
            }
            if (! $run->finished_at) {
                $run->update(['progress_percent' => 100, 'finished_at' => now()]);
            }
            $loadTest->update(['stop_reason' => $stopReason]);

            if ($project->current_step < Project::STEP_LOAD_TEST) {
                $project->update(['current_step' => Project::STEP_LOAD_TEST]);
            }

            AuditLog::record('load_test.completed', $interrupted ? 'blocked' : 'success', $run->started_by, $project->id, $target->hostname, [
                'load_test_id' => $loadTest->id,
                'stop_reason' => $stopReason,
                'requests_sent' => $totalSent,
            ]);
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'error_message' => SecretRedactor::redactString($e->getMessage()), 'finished_at' => now()]);
            AuditLog::record('load_test.failed', 'failure', $run->started_by, $project->id, $target->hostname, ['load_test_id' => $loadTest->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function percentile(array $values, int $percentile): int
    {
        if ($values === []) return 0;
        sort($values, SORT_NUMERIC);
        $index = max(0, (int) ceil(($percentile / 100) * count($values)) - 1);
        return (int) $values[$index];
    }

    private function port(Target $target): int
    {
        return (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
    }
}
