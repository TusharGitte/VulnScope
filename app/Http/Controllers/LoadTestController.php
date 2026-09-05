<?php

namespace App\Http\Controllers;

use App\Jobs\RunControlledLoadTestJob;
use App\Models\AuditLog;
use App\Models\LoadTest;
use App\Models\Project;
use App\Services\ScopeEnforcementService;
use App\Services\TargetUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoadTestController extends Controller
{
    public function __construct(private ScopeEnforcementService $scope)
    {
    }

    public function show(Project $project): View
    {
        $project->load(['targets', 'scopeRules', 'loadTests.metrics']);
        $target = $project->targets()->first();
        $scope = $project->activeScopeRule();
        $latestLoadTest = $project->loadTests()->latest()->first();
        return view('load-test.show', compact('project', 'target', 'scope', 'latestLoadTest'));
    }

    public function start(Request $request, Project $project): RedirectResponse
    {
        $target = $project->targets()->first();
        if (! $target) return back()->with('error', 'No target found for this project.');
        if ($project->hasAnyActiveRun()) return back()->with('error', 'Another assessment job is already running for this project.');

        $scope = $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $target->normalized_url);

        $validated = $request->validate([
            'confirm_authorization' => ['accepted'],
            'endpoint' => ['required', 'string', 'max:2000', 'url'],
            'http_method' => ['required', 'in:GET,HEAD,POST,PUT,PATCH'],
            'request_body_template' => ['nullable', 'string', 'max:50000'],
            'max_rps' => ['required', 'integer', 'min:1', 'max:' . $scope->effectiveMaxRequestRate()],
            'concurrency' => ['required', 'integer', 'min:1', 'max:' . $scope->effectiveMaxConcurrency()],
            'duration_seconds' => ['required', 'integer', 'min:10', 'max:' . $scope->effectiveMaxDurationSeconds()],
            'max_total_requests' => ['required', 'integer', 'min:10', 'max:' . $scope->effectiveMaxTotalRequests()],
            'ramp_up_seconds' => ['required', 'integer', 'min:0', 'max:' . $scope->effectiveMaxDurationSeconds()],
            'request_timeout_ms' => ['required', 'integer', 'min:100', 'max:30000'],
            'error_rate_threshold_percent' => ['required', 'integer', 'min:1', 'max:' . config('vapt.max_error_rate_percent')],
            'latency_threshold_ms' => ['required', 'integer', 'min:100', 'max:' . config('vapt.max_latency_ms')],
        ]);
        if (! empty($validated['request_body_template']) && in_array($validated['http_method'], ['GET', 'HEAD'], true)) {
            return back()->withInput()->withErrors(['request_body_template' => 'Request bodies are only allowed for POST, PUT, or PATCH tests.']);
        }

        try {
            $endpoint = TargetUrlService::normalize($validated['endpoint']);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['endpoint' => $e->getMessage()]);
        }
        $this->scope->assertInScope(
            $project,
            $endpoint['hostname'],
            $endpoint['port'],
            url: $endpoint['normalized_url']
        );
        $this->scope->assertResolvedIpsInScope($project, $endpoint['hostname']);

        if ($project->current_step > 2) {
            $project->update(['current_step' => 2, 'status' => 'active']);
        }

        $run = $project->scanRuns()->create([
            'target_id' => $target->id,
            'stage' => 'load_test',
            'status' => 'queued',
            'progress_percent' => 0,
            'started_by' => $request->user()->id,
            'config' => ['endpoint' => $endpoint['normalized_url'], 'http_method' => $validated['http_method']],
        ]);

        $loadTest = $project->loadTests()->create([
            'target_id' => $target->id,
            'scan_run_id' => $run->id,
            'endpoint' => $endpoint['normalized_url'],
            'http_method' => strtoupper($validated['http_method']),
            'request_body_template' => $validated['request_body_template'] ?? null,
            'virtual_users' => (int) $validated['concurrency'],
            'concurrency' => (int) $validated['concurrency'],
            'ramp_up_seconds' => (int) $validated['ramp_up_seconds'],
            'duration_seconds' => (int) $validated['duration_seconds'],
            'max_rps' => (int) $validated['max_rps'],
            'max_total_requests' => (int) $validated['max_total_requests'],
            'request_timeout_ms' => (int) $validated['request_timeout_ms'],
            'error_rate_threshold_percent' => (int) $validated['error_rate_threshold_percent'],
            'latency_threshold_ms' => (int) $validated['latency_threshold_ms'],
            'explicitly_confirmed' => true,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        RunControlledLoadTestJob::dispatch($loadTest->id)->onQueue('vapt');
        AuditLog::record('load_test.queued', 'success', $request->user()->id, $project->id, $target->hostname, [
            'load_test_id' => $loadTest->id,
            'scan_run_id' => $run->id,
            'max_rps' => $loadTest->max_rps,
            'concurrency' => $loadTest->concurrency,
            'duration_seconds' => $loadTest->duration_seconds,
            'max_total_requests' => $loadTest->max_total_requests,
            'endpoint' => $loadTest->endpoint,
            'http_method' => $loadTest->http_method,
        ]);

        return redirect()->route('load-test.show', $project)->with('success', 'Controlled load test queued with the confirmed safety limits.');
    }

    public function stop(Request $request, Project $project, LoadTest $loadTest): RedirectResponse
    {
        abort_unless($loadTest->project_id === $project->id, 404);
        $run = $loadTest->scanRun;
        if ($run && $run->isActive()) {
            $run->update(['status' => 'interrupted', 'finished_at' => now()]);
            $loadTest->update(['stop_reason' => 'manual_stop']);
            AuditLog::record('load_test.stopped_manually', 'success', $request->user()->id, $project->id, $loadTest->endpoint, ['load_test_id' => $loadTest->id]);
        }
        return redirect()->route('load-test.show', $project)->with('success', 'Emergency stop recorded. The worker will stop at its next safe checkpoint.');
    }

    private function port($target): int
    {
        return (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
    }
}
