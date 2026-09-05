@extends('layouts.app', ['title' => 'Step 2: Security Assessment — ' . $project->name])

@section('content')
@if(isset($activeRun) && $activeRun)
    <div class="card" style="border-left: 4px solid var(--warning); margin-bottom: 1.5rem;">
        <strong>Scan in progress:</strong> {{ $activeRun->progress_percent }}% &mdash;
        <form action="{{ route('scan.cancel', [$project, $activeRun]) }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-secondary btn-sm">Cancel Scan</button>
        </form>
    </div>
    @if ($activeRun->isStalled())
        <div class="alert alert-danger">
            <span>⚠</span>
            <div>
                <strong>This scan looks stuck.</strong> It's been running for {{ $activeRun->created_at->diffForHumans(null, true) }} with no progress.
                This almost always means the background queue worker isn't running. Start it with
                <code class="font-mono">php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800</code>
                (or <code class="font-mono">./run-worker.sh</code>) and this page will pick up progress automatically.
            </div>
        </div>
    @endif
    <meta http-equiv="refresh" content="5">
@endif
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 2 of 4</div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Vulnerability Assessment Scan</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a> &bull; Target: <code class="font-mono" style="color: var(--accent-cyan);">{{ $project->targets->first()?->hostname }}</code>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm">&larr; Project Overview</a>
        @if ($project->canEnterStep(3))
            <a href="{{ route('load-test.show', $project) }}" class="btn btn-success btn-sm">Proceed to Step 3: Load Test &rarr;</a>
        @endif
    </div>
</div>

<div class="card" style="border-left: 4px solid var(--primary); margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h2 style="font-size: 1.1rem; color: #fff; margin-bottom: 0.25rem;">Automated Security Check Suite</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Evaluates transport security, security headers, clickjacking protections, MIME sniffing prevention, and information disclosure.
            </p>
        </div>
        <div>
            <form action="{{ route('scan.start', $project) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" {{ $activeRun ? 'disabled' : '' }}>
                    {{ $activeRun ? 'Scan Running…' : ($findings->isNotEmpty() ? '⟳ Re-run Security Scan' : '▶ Start Security Scan') }}
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Findings Section -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Identified Vulnerabilities & Weaknesses ({{ $findings->count() }})</h2>
        <div style="display: flex; gap: 0.5rem;">
            @php
                $sevBadges = [
                    'critical' => 'badge-critical',
                    'high' => 'badge-high',
                    'medium' => 'badge-medium',
                    'low' => 'badge-low',
                    'informational' => 'badge-info',
                ];
            @endphp
            @foreach (['critical', 'high', 'medium', 'low', 'informational'] as $sev)
                @php $cnt = $findings->where('severity', $sev)->count(); @endphp
                @if ($cnt > 0)
                    <span class="badge {{ $sevBadges[$sev] }}">{{ $cnt }} {{ ucfirst($sev) }}</span>
                @endif
            @endforeach
        </div>
    </div>

    @if ($findings->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🛡️</div>
            <p style="margin-bottom: 1rem;">No findings recorded yet. Start the bounded security assessment when Step 1 is complete.</p>
            <form action="{{ route('scan.start', $project) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" {{ $activeRun ? 'disabled' : '' }}>▶ {{ $activeRun ? 'Scan Running…' : 'Run Vulnerability Scan Now' }}</button>
            </form>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach ($findings as $finding)
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span class="badge {{ $sevBadges[$finding->severity] ?? 'badge-info' }}">
                                {{ strtoupper($finding->severity) }}
                            </span>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff;">{{ $finding->title }}</h3>
                        </div>
                        <span class="badge badge-info">{{ ucfirst($finding->status) }}</span>
                    </div>

                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.75rem;">
                        {{ $finding->description }}
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: var(--bg-card); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 0.75rem;">
                        <div>
                            <strong style="color: #fbbf24;">Impact:</strong>
                            <span style="color: var(--text-muted);">{{ $finding->impact }}</span>
                        </div>
                        <div>
                            <strong style="color: #34d399;">Remediation:</strong>
                            <span style="color: var(--text-muted);">{{ $finding->remediation }}</span>
                        </div>
                    </div>

                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                        Target URL: <code class="font-mono" style="color: var(--accent-cyan);">{{ $finding->url }}</code>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
