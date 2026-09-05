<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 42px 42px 48px 42px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.45; }
        h1,h2,h3 { color: #0f172a; }
        h1 { font-size: 26px; margin: 0 0 8px; }
        h2 { font-size: 17px; margin: 18px 0 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 12px 0 5px; }
        .muted { color: #64748b; }
        .cover { height: 720px; display: block; padding-top: 170px; }
        .cover .eyebrow { text-transform: uppercase; letter-spacing: 2px; color: #475569; font-size: 9px; }
        .meta, .data { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        .meta td, .data td, .data th { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        .data th { background: #f1f5f9; text-align: left; }
        .page-break { page-break-before: always; }
        .finding { border: 1px solid #cbd5e1; padding: 9px; margin: 8px 0; page-break-inside: avoid; }
        .severity { display: inline-block; font-weight: bold; padding: 2px 5px; border: 1px solid #94a3b8; font-size: 8px; }
        .code { font-family: DejaVu Sans Mono, monospace; white-space: pre-wrap; word-break: break-all; }
        img { max-width: 100%; max-height: 320px; }
    </style>
</head>
<body>
<div class="cover">
    <div class="eyebrow">Authorized Security Assessment</div>
    <h1>{{ $title }}</h1>
    <p class="muted">Prepared {{ now()->format('Y-m-d H:i') }}</p>
    <table class="meta">
        <tr><td width="30%"><strong>Client</strong></td><td>{{ $project->client_name ?: 'Not specified' }}</td></tr>
        <tr><td><strong>Project</strong></td><td>{{ $project->name }}</td></tr>
        <tr><td><strong>Owner</strong></td><td>{{ $project->owner?->name ?? 'N/A' }}</td></tr>
        <tr><td><strong>Report Status</strong></td><td>Final assessment deliverable</td></tr>
    </table>
    <p class="muted">This report contains only results recorded by the VAPT Platform. Automated checks are passive/safe validation checks and require analyst confirmation before being treated as a confirmed vulnerability.</p>
</div>

<h2>1. Cover Page</h2><p>{{ $title }}</p>
<h2>2. Client / Project Information</h2>
<table class="meta"><tr><td>Client</td><td>{{ $project->client_name ?: 'Not specified' }}</td></tr><tr><td>Project</td><td>{{ $project->name }}</td></tr><tr><td>Description</td><td>{{ $project->description ?: 'N/A' }}</td></tr></table>

<h2>3. Assessment Scope</h2>
@if($scope)
<table class="meta">
<tr><td>Allowed domains</td><td>{{ implode(', ', $scope->allowed_domains ?? []) }}</td></tr>
<tr><td>Allowed IP/CIDR</td><td>{{ implode(', ', $scope->allowed_ip_ranges ?? []) ?: 'Not restricted by IP list' }}</td></tr>
<tr><td>Excluded hosts</td><td>{{ implode(', ', $scope->excluded_hosts ?? []) ?: 'None recorded' }}</td></tr>
<tr><td>Allowed ports</td><td>{{ implode(', ', $scope->allowed_ports ?? []) ?: 'Ports observed on target URL' }}</td></tr>
<tr><td>Allowed endpoints</td><td>{{ implode(', ', $scope->allowed_endpoints ?? []) ?: 'All in-scope paths on allowed hosts' }}</td></tr>
</table>
@else <p>No scope record was available.</p> @endif

<h2>4. Authorization / Rules of Engagement Summary</h2>
@if($scope)
<table class="meta">
<tr><td>Authorization confirmed</td><td>{{ $scope->confirmed_at?->format('Y-m-d H:i:s') }}</td></tr>
<tr><td>Testing window</td><td>{{ $scope->window_start?->format('Y-m-d H:i') }} to {{ $scope->window_end?->format('Y-m-d H:i') }}</td></tr>
<tr><td>Maximum request rate</td><td>{{ $scope->effectiveMaxRequestRate() }} req/s</td></tr>
<tr><td>Maximum concurrency</td><td>{{ $scope->effectiveMaxConcurrency() }}</td></tr>
<tr><td>Maximum duration</td><td>{{ $scope->effectiveMaxDurationSeconds() }} seconds</td></tr>
<tr><td>Maximum total requests</td><td>{{ $scope->effectiveMaxTotalRequests() }}</td></tr>
<tr><td>Authenticated testing</td><td>{{ $scope->authenticated_testing_allowed ? 'Allowed' : 'Not enabled' }}</td></tr>
</table>
<p>{{ $scope->authorization_notes ?: 'No additional authorization notes recorded.' }}</p>
@endif

<h2>5. Executive Summary</h2>
<p>The assessment followed the required four-stage workflow. Step 1 gathered observable infrastructure and HTTP information; Step 2 performed bounded, non-destructive web security checks; Step 3 recorded controlled load/performance behavior within the approved ceilings; and Step 4 compiled analyst findings into this report. Each stage status is preserved in the run appendix, including any interrupted or failed stage.</p>
<table class="data"><thead><tr><th>Severity</th><th>Count</th></tr></thead><tbody>@foreach($severitySummary as $severity => $count)<tr><td>{{ ucfirst($severity) }}</td><td>{{ $count }}</td></tr>@endforeach</tbody></table>

<h2>6. Target Overview</h2>
<table class="data"><thead><tr><th>Target</th><th>Hostname</th><th>Status</th></tr></thead><tbody>@foreach($project->targets as $target)<tr><td>{{ $target->normalized_url }}</td><td>{{ $target->hostname }}</td><td>{{ ucfirst($target->status) }}</td></tr>@endforeach</tbody></table>

<h2>7. Step 1 — Reconnaissance Results</h2>
@if($reconRun)
<table class="data"><thead><tr><th>Section</th><th>Key</th><th>Value</th><th>Confidence</th><th>Source</th></tr></thead><tbody>
@foreach($reconRun->reconResults as $item)<tr><td>{{ $item->section }}</td><td>{{ $item->key }}</td><td class="code">{{ $item->value }}</td><td>{{ $item->confidence }}</td><td>{{ $item->source }}</td></tr>@endforeach
</tbody></table>
@else <p>No recon run was available.</p> @endif

<h2>8. Technology / Infrastructure Summary</h2>
@if($reconRun)<table class="data"><thead><tr><th>Property</th><th>Observed</th><th>Confidence</th></tr></thead><tbody>
@foreach($reconRun->reconResults->where('section','tech_stack')->merge($reconRun->reconResults->where('section','network')->take(30)) as $item)<tr><td>{{ $item->key }}</td><td>{{ $item->value }}</td><td>{{ $item->confidence }}</td></tr>@endforeach
</tbody></table>@else <p>No data.</p>@endif

<h2>9. Step 2 — Security Findings</h2>
<p>{{ $project->findings->count() }} finding(s) were recorded. Automated findings remain in a reviewable state until the analyst validates them.</p>
@foreach($project->findings as $finding)<div class="finding"><span class="severity">{{ strtoupper($finding->severity) }}</span> <strong>{{ $finding->title }}</strong><br><span class="muted">Confidence: {{ $finding->confidence }} · Status: {{ str_replace('_',' ',ucfirst($finding->status)) }}</span><p>{{ $finding->description }}</p></div>@endforeach

<h2>10. Step 3 — Load / Performance Results</h2>
@if($loadTest)
<table class="data"><tr><th>Endpoint</th><td>{{ $loadTest->endpoint }}</td></tr><tr><th>Configured RPS</th><td>{{ $loadTest->max_rps }}</td></tr><tr><th>Configured concurrency</th><td>{{ $loadTest->concurrency }}</td></tr><tr><th>Duration ceiling</th><td>{{ $loadTest->duration_seconds }} seconds</td></tr><tr><th>Max requests</th><td>{{ $loadTest->max_total_requests }}</td></tr><tr><th>Stop reason</th><td>{{ $loadTest->stop_reason ?: 'N/A' }}</td></tr></table>
@foreach($loadTest->metrics as $metric)<table class="data"><tr><th>Sampled</th><td>{{ $metric->sampled_at?->format('Y-m-d H:i:s') }}</td></tr><tr><th>RPS</th><td>{{ $metric->requests_per_sec }}</td></tr><tr><th>P50/P95/P99</th><td>{{ $metric->p50_latency_ms }} / {{ $metric->p95_latency_ms }} / {{ $metric->p99_latency_ms }} ms</td></tr><tr><th>Error / timeout</th><td>{{ $metric->error_percent }}% / {{ $metric->timeout_percent }}%</td></tr><tr><th>Concurrent users</th><td>{{ $metric->concurrent_users }}</td></tr></table>@endforeach
@else <p>No load-test record available.</p>@endif

<h2>11. Risk Summary</h2>
<table class="data"><thead><tr><th>Severity</th><th>Count</th><th>Interpretation</th></tr></thead><tbody>@foreach($severitySummary as $severity => $count)<tr><td>{{ ucfirst($severity) }}</td><td>{{ $count }}</td><td>{{ $severity === 'critical' || $severity === 'high' ? 'Priority remediation required' : ($severity === 'medium' ? 'Review and remediate based on risk' : 'Track and address as appropriate') }}</td></tr>@endforeach</tbody></table>

<h2>12. Detailed Findings</h2>
@foreach($project->findings as $finding)<div class="finding"><h3>{{ $finding->title }}</h3><div><strong>Severity:</strong> {{ ucfirst($finding->severity) }} &nbsp; <strong>Confidence:</strong> {{ ucfirst($finding->confidence) }} &nbsp; <strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$finding->status)) }}</div><p><strong>Affected URL:</strong> {{ $finding->url }}</p><p><strong>Description:</strong> {{ $finding->description }}</p><p><strong>Impact:</strong> {{ $finding->impact ?: 'Not recorded.' }}</p><p><strong>Reproduction guidance:</strong> {{ $finding->reproduction_guidance ?: 'Validate manually within approved scope.' }}</p><p><strong>Remediation:</strong> {{ $finding->remediation ?: 'Not recorded.' }}</p>@if($finding->references)<p><strong>References:</strong></p><ul>@foreach($finding->references as $reference)<li>{{ $reference }}</li>@endforeach</ul>@endif</div>@endforeach

<h2>13. Evidence / Screenshots</h2>
@foreach($project->findings as $finding)
@foreach($finding->evidence as $evidence)
<div class="finding"><strong>{{ $finding->title }}</strong><br><span class="muted">Type: {{ $evidence->type }} · Captured: {{ $evidence->created_at?->format('Y-m-d H:i') }}</span>
@if($evidence->storage_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($evidence->storage_path))
@php $ext = strtolower(pathinfo($evidence->storage_path, PATHINFO_EXTENSION)); $mime = in_array($ext,['jpg','jpeg']) ? 'image/jpeg' : 'image/'.($ext ?: 'png'); $dataUri = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('local')->get($evidence->storage_path)); @endphp
@if(in_array($ext,['jpg','jpeg','png','webp']))<p><img src="{{ $dataUri }}"></p>@else<p>File evidence stored privately and available from the platform.</p>@endif
@endif
@if($evidence->content)<p class="code">{{ $evidence->content }}</p>@endif
</div>
@endforeach
@endforeach

<h2>14. Reproduction Guidance</h2>
<p>Reproduction steps are intentionally scoped to safe analyst validation. The platform does not include destructive exploit automation, credential theft, persistence, lateral movement, or unrestricted attack modes.</p>
@foreach($project->findings as $finding)<div class="finding"><strong>{{ $finding->title }}</strong><p class="code">{{ $finding->reproduction_guidance ?: 'No additional guidance recorded.' }}</p></div>@endforeach

<h2>15. Remediation Recommendations</h2>
@foreach($project->findings as $finding)<div class="finding"><strong>{{ $finding->title }}</strong><p>{{ $finding->remediation ?: 'No remediation recorded.' }}</p></div>@endforeach

<h2>16. Limitations</h2>
<ul><li>Only observable and in-scope information was collected.</li><li>Technology fingerprints are not treated as certain unless directly evidenced.</li><li>Automated Step 2 checks are non-destructive and should be analyst-validated.</li><li>Hidden/origin infrastructure behind CDNs/WAFs is not bypassed.</li><li>Historical registration information may be unavailable unless an explicitly configured public provider is used.</li></ul>

<h2>17. Conclusion</h2>
<p>The assessment records the target, scope, executed workflow stages, observed evidence, performance metrics, and analyst findings available to the platform at report time. Remediation priority should be driven by severity, confidence, business impact, and owner validation.</p>

<h2>18. Appendix</h2>
<table class="data"><thead><tr><th>Run</th><th>Stage</th><th>Status</th><th>Started</th><th>Finished</th></tr></thead><tbody>@foreach($project->scanRuns()->latest()->get() as $run)<tr><td>#{{ $run->id }}</td><td>{{ $run->stage }}</td><td>{{ $run->status }}</td><td>{{ $run->started_at?->format('Y-m-d H:i:s') }}</td><td>{{ $run->finished_at?->format('Y-m-d H:i:s') }}</td></tr>@endforeach</tbody></table>
<p class="muted">Generated by VAPT Platform. Confidential — Authorized assessment use only.</p>
</body></html>
