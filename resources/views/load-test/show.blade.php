@extends('layouts.app', ['title' => 'Step 3: Controlled Load Test — ' . $project->name])
@section('content')
@if($latestLoadTest && $latestLoadTest->scanRun && $latestLoadTest->scanRun->isActive())
<meta http-equiv="refresh" content="5">
@endif
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem"><div><div style="font-size:.85rem;color:var(--accent-cyan);font-weight:700;text-transform:uppercase">Step 3 of 4</div><h1 style="color:#fff">Controlled Performance & Resilience Testing</h1><p style="color:var(--text-muted)">Target: <code>{{ $target?->hostname }}</code></p></div><a href="{{ route('projects.show',$project) }}" class="btn btn-secondary btn-sm">&larr; Project</a></div>
@if($scope)
<div class="card" style="border-left:4px solid var(--warning)"><h2 style="color:#fbbf24">Safety Safeguards Active</h2><p style="color:var(--text-muted)">Hard platform ceilings: {{ config('vapt.max_request_rate') }} RPS, {{ config('vapt.max_concurrency') }} concurrency, {{ config('vapt.max_duration_seconds') }} seconds, {{ config('vapt.max_total_requests') }} total requests. Tests stop automatically on error/latency thresholds and can be emergency-stopped.</p></div>
<div style="display:grid;grid-template-columns:1fr;gap:1.5rem">
<div class="card"><div class="card-header"><h2 class="card-title">Configure & Confirm Test</h2><span class="badge badge-info">Explicit confirmation required</span></div>
<form action="{{ route('load-test.start',$project) }}" method="POST">@csrf
<div class="form-group"><label class="form-label">Endpoint</label><input class="form-input" type="url" name="endpoint" value="{{ old('endpoint', $target?->normalized_url) }}" required><small style="color:var(--text-muted)">Must be in the approved scope.</small></div>
<div class="form-group"><label class="form-label">HTTP Method</label><select class="form-select" name="http_method"><option {{ old('http_method', 'GET') === 'GET' ? 'selected' : '' }}>GET</option><option {{ old('http_method') === 'HEAD' ? 'selected' : '' }}>HEAD</option><option {{ old('http_method') === 'POST' ? 'selected' : '' }}>POST</option><option {{ old('http_method') === 'PUT' ? 'selected' : '' }}>PUT</option><option {{ old('http_method') === 'PATCH' ? 'selected' : '' }}>PATCH</option></select></div>
<div class="form-group"><label class="form-label">Request Body Template</label><textarea class="form-textarea font-mono" name="request_body_template" rows="3" maxlength="50000" placeholder="Optional raw body (for POST/PUT/PATCH). Do not paste secrets.">{{ old('request_body_template') }}</textarea></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem"><div class="form-group"><label class="form-label">Max RPS</label><input class="form-input" type="number" name="max_rps" min="1" max="{{ $scope->effectiveMaxRequestRate() }}" value="5" required></div><div class="form-group"><label class="form-label">Concurrency</label><input class="form-input" type="number" name="concurrency" min="1" max="{{ $scope->effectiveMaxConcurrency() }}" value="2" required></div><div class="form-group"><label class="form-label">Duration (sec)</label><input class="form-input" type="number" name="duration_seconds" min="10" max="{{ $scope->effectiveMaxDurationSeconds() }}" value="10" required></div><div class="form-group"><label class="form-label">Max Requests</label><input class="form-input" type="number" name="max_total_requests" min="10" max="{{ $scope->effectiveMaxTotalRequests() }}" value="20" required></div><div class="form-group"><label class="form-label">Ramp-up (sec)</label><input class="form-input" type="number" name="ramp_up_seconds" min="0" max="{{ $scope->effectiveMaxDurationSeconds() }}" value="2" required></div><div class="form-group"><label class="form-label">Timeout (ms)</label><input class="form-input" type="number" name="request_timeout_ms" min="100" max="30000" value="5000" required></div><div class="form-group"><label class="form-label">Error stop (%)</label><input class="form-input" type="number" name="error_rate_threshold_percent" min="1" max="{{ config('vapt.max_error_rate_percent') }}" value="25" required></div><div class="form-group"><label class="form-label">Latency stop (ms)</label><input class="form-input" type="number" name="latency_threshold_ms" min="100" max="{{ config('vapt.max_latency_ms') }}" value="10000" required></div></div>
<label style="display:flex;gap:.5rem;align-items:flex-start;margin-top:1rem"><input type="checkbox" name="confirm_authorization" value="1" required><span style="color:#fff;font-size:.9rem">I explicitly confirm that this exact endpoint, method, request body, limits, duration, and stop conditions are authorized and are within the approved scope.</span></label><button class="btn btn-primary" style="width:100%;margin-top:1rem">▶ Queue Controlled Load Test</button></form></div>
</div>
@if($latestLoadTest)
<div class="card"><div class="card-header"><h2 class="card-title">Latest Test</h2><span class="badge badge-info">{{ $latestLoadTest->scanRun?->status ?? 'queued' }}</span></div><p style="color:var(--text-muted)">Stop reason: <strong>{{ $latestLoadTest->stop_reason ?: 'running' }}</strong>. Endpoint: <code>{{ $latestLoadTest->endpoint }}</code></p>
@if($latestLoadTest->scanRun?->isStalled())
<div class="alert alert-danger">
    <span>⚠</span>
    <div>
        <strong>This load test looks stuck.</strong> It's been {{ $latestLoadTest->scanRun->status }} for {{ $latestLoadTest->scanRun->created_at->diffForHumans(null, true) }} with no progress.
        This almost always means the background queue worker isn't running. Start it with
        <code class="font-mono">php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800</code>
        (or <code class="font-mono">./run-worker.sh</code>) and this page will pick up progress automatically.
    </div>
</div>
@endif
@if($latestLoadTest->scanRun?->isActive())<form action="{{ route('load-test.stop',[$project,$latestLoadTest]) }}" method="POST" style="margin-top:1rem">@csrf<button class="btn btn-secondary">Emergency STOP</button></form>@endif
@foreach($latestLoadTest->metrics()->latest('sampled_at')->get() as $metric)<div style="margin-top:1rem"><strong>Sample {{ $metric->sampled_at?->format('H:i:s') }}</strong> — Sent {{ $metric->requests_sent }}, p50/p95/p99 {{ $metric->p50_latency_ms }}/{{ $metric->p95_latency_ms }}/{{ $metric->p99_latency_ms }} ms, errors {{ $metric->error_percent }}%, timeouts {{ $metric->timeout_percent }}%</div>@endforeach
@if($project->canEnterStep(4))<a href="{{ route('report.show',$project) }}" class="btn btn-success" style="margin-top:1rem">Proceed to Step 4 →</a>@endif</div>
@endif
@else
<div class="card"><p style="color:#f87171">No active authorization scope. Return to Scope Setup before running a performance test.</p><a class="btn btn-primary" href="{{ route('scope.edit',$project) }}">Configure Scope</a></div>
@endif
@endsection
