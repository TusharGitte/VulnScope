@extends('layouts.app', ['title' => 'Step 1: Reconnaissance — ' . $project->name])

@section('content')
@if($latestRun && $latestRun->isActive())<meta http-equiv="refresh" content="5">@endif
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 1 of 4</div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Website Reconnaissance</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a> &bull; Target: <code class="font-mono" style="color: var(--accent-cyan);">{{ $project->targets->first()?->hostname }}</code>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm">&larr; Project Overview</a>
        @if ($latestRun && $latestRun->isActive())
            <form action="{{ route('recon.cancel', [$project, $latestRun]) }}" method="POST" style="display:inline">@csrf<button type="submit" class="btn btn-secondary btn-sm">Cancel Run</button></form>
        @endif
        @if ($project->canEnterStep(2))
            <a href="{{ route('scan.show', $project) }}" class="btn btn-success btn-sm">Proceed to Step 2: Scan &rarr;</a>
        @endif
    </div>
</div>

<div class="card" style="border-left: 4px solid var(--accent-cyan); margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h2 style="font-size: 1.1rem; color: #fff; margin-bottom: 0.25rem;">Information Gathering Control</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Safe observable data collection: DNS records, HTTP responses, security headers, technology fingerprints.
            </p>
        </div>
        <div>
            <form action="{{ route('recon.start', $project) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" {{ ($latestRun && $latestRun->isActive()) ? 'disabled' : '' }}>
                    {{ ($latestRun && $latestRun->isActive()) ? 'Recon Running…' : ($latestRun ? '⟳ Re-run Reconnaissance' : '▶ Start Reconnaissance') }}
                </button>
            </form>
        </div>
    </div>
</div>

@if ($latestRun)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Reconnaissance Run Details</h2>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="badge {{ $latestRun->status === 'completed' ? 'badge-low' : ($latestRun->status === 'running' ? 'badge-info' : 'badge-critical') }}">
                    {{ ucfirst($latestRun->status) }}
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Run #{{ $latestRun->id }} &bull; {{ $latestRun->created_at->diffForHumans() }} &bull; Progress {{ $latestRun->progress_percent }}%</span>
            </div>
        </div>

        @if ($latestRun->isStalled())
            <div class="alert alert-danger" style="margin-top: 0; margin-bottom: 1.5rem;">
                <span>⚠</span>
                <div>
                    <strong>This run looks stuck.</strong> It's been {{ $latestRun->status }} for {{ $latestRun->created_at->diffForHumans(null, true) }} with no progress.
                    This almost always means the background queue worker isn't running. Start it with
                    <code class="font-mono">php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800</code>
                    (or <code class="font-mono">./run-worker.sh</code>) and this page will pick up progress automatically.
                </div>
            </div>
        @endif

        @if ($reconResults->isEmpty())
            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No data collected yet.</p>
        @else
            @foreach ($reconResults as $section => $items)
                <div style="margin-bottom: 1.75rem;">
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.35rem;">
                        {{ strtoupper($section) }} ({{ count($items) }})
                    </h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Property / Key</th>
                                    <th>Observed Value</th>
                                    <th style="width: 15%;">Confidence</th>
                                    <th style="width: 20%;">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="font-weight: 600; color: #fff;">{{ $item->key }}</td>
                                        <td>
                                            <span class="font-mono" style="word-break: break-all; color: #e2e8f0;">{{ $item->value }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $item->confidence }}</span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $item->source }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@else
    <div class="card" style="text-align: center; padding: 3rem 1rem;">
        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</div>
        <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #fff;">No Reconnaissance Data Yet</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem; max-width: 500px; margin-left: auto; margin-right: auto;">
            Target has been validated against authorized scope. Click the button below to gather DNS records, headers, and server details.
        </p>
        <form action="{{ route('recon.start', $project) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">▶ Execute Reconnaissance</button>
        </form>
    </div>
@endif
@endsection
