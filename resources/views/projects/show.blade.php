@extends('layouts.app', ['title' => $project->name])

@section('content')
<!-- Project Title & Actions -->
<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 2rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <h1 style="font-size: 1.85rem; font-weight: 700; color: #fff;">{{ $project->name }}</h1>
            <span class="badge {{ $project->status === 'completed' ? 'badge-low' : ($project->status === 'active' ? 'badge-info' : 'badge-medium') }}">
                {{ ucfirst($project->status) }}
            </span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Client: <strong style="color: #fff;">{{ $project->client_name ?? 'Internal Project' }}</strong> &bull; Created: {{ $project->created_at->format('M d, Y') }}
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <a href="{{ route('scope.edit', $project) }}" class="btn btn-secondary btn-sm">⚙ Scope Rules</a>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary btn-sm">✎ Edit Details</a>
        <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete project \'{{ addslashes($project->name) }}\' and all associated assessment data?');" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary btn-sm" style="color: #f87171;" title="Delete Project">
                🗑 Delete Project
            </button>
        </form>
    </div>
</div>

<!-- Workflow Stepper Pipeline -->
<div class="workflow-steps">
    <!-- Step 1 -->
    <a href="{{ $activeScope ? route('recon.show', $project) : '#' }}" class="step-item {{ $project->current_step >= 1 ? 'completed' : ($activeScope ? 'active' : '') }}" style="{{ ! $activeScope ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
        <div class="step-num">{{ $project->current_step >= 1 ? '✓' : '1' }}</div>
        <div>
            <div style="font-weight: 700; font-size: 0.9rem;">Step 1: Recon</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">
                {{ $project->current_step >= 1 ? 'Completed' : ($activeScope ? 'Ready' : 'Scope Required') }}
            </div>
        </div>
    </a>

    <!-- Step 2 -->
    <a href="{{ $project->canEnterStep(2) ? route('scan.show', $project) : '#' }}" class="step-item {{ $project->current_step >= 2 ? 'completed' : ($project->canEnterStep(2) ? 'active' : '') }}" style="{{ ! $project->canEnterStep(2) ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
        <div class="step-num">{{ $project->current_step >= 2 ? '✓' : '2' }}</div>
        <div>
            <div style="font-weight: 700; font-size: 0.9rem;">Step 2: Security Scan</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">
                {{ $project->current_step >= 2 ? 'Completed' : ($project->canEnterStep(2) ? 'Unlocked' : 'Locked') }}
            </div>
        </div>
    </a>

    <!-- Step 3 -->
    <a href="{{ $project->canEnterStep(3) ? route('load-test.show', $project) : '#' }}" class="step-item {{ $project->current_step >= 3 ? 'completed' : ($project->canEnterStep(3) ? 'active' : '') }}" style="{{ ! $project->canEnterStep(3) ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
        <div class="step-num">{{ $project->current_step >= 3 ? '✓' : '3' }}</div>
        <div>
            <div style="font-weight: 700; font-size: 0.9rem;">Step 3: Load Test</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">
                {{ $project->current_step >= 3 ? 'Completed' : ($project->canEnterStep(3) ? 'Unlocked' : 'Locked') }}
            </div>
        </div>
    </a>

    <!-- Step 4 -->
    <a href="{{ $project->canEnterStep(4) ? route('report.show', $project) : '#' }}" class="step-item {{ $project->current_step >= 4 ? 'completed' : ($project->canEnterStep(4) ? 'active' : '') }}" style="{{ ! $project->canEnterStep(4) ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
        <div class="step-num">{{ $project->current_step >= 4 ? '✓' : '4' }}</div>
        <div>
            <div style="font-weight: 700; font-size: 0.9rem;">Step 4: Report</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">
                {{ $project->current_step >= 4 ? 'Generated' : ($project->canEnterStep(4) ? 'Unlocked' : 'Locked') }}
            </div>
        </div>
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Left Column: Target & Next Actions -->
    <div>
        <!-- Target Configuration -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Assessment Target</h2>
                @if ($project->targets->isNotEmpty())
                    <span class="badge badge-info">Target Configured</span>
                @else
                    <span class="badge badge-warning">Target Missing</span>
                @endif
            </div>

            @if ($project->targets->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                    Every assessment must have an authorized target URL. Enter the target web application endpoint below:
                </p>
                <form action="{{ route('targets.store', $project) }}" method="POST">
                    @csrf
                    <div style="display: flex; gap: 0.75rem;">
                        <input type="url" name="input_url" placeholder="https://example.com" required class="form-input" style="flex: 1;">
                        <button type="submit" class="btn btn-primary">Add Target</button>
                    </div>
                </form>
            @else
                @foreach ($project->targets as $target)
                    <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <div>
                            <div style="font-weight: 600; font-size: 1rem; color: #fff;">{{ $target->input_url }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                Host: <code class="font-mono" style="color: var(--accent-cyan);">{{ $target->hostname }}</code> &bull; Status: {{ ucfirst($target->status) }}
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="{{ route('targets.edit', [$project, $target]) }}" class="btn btn-secondary btn-sm" title="Edit Target">✎ Edit</a>
                            <form action="{{ route('targets.destroy', [$project, $target]) }}" method="POST" onsubmit="return confirm('Remove target?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="color: #f87171;">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Next Action Card -->
        <div class="card" style="border-left: 4px solid var(--primary);">
            <div class="card-header">
                <h2 class="card-title">Next Action in Assessment Workflow</h2>
            </div>
            @if ($project->targets->isEmpty())
                <p style="color: var(--text-muted); margin-bottom: 1rem;">Add a target web application above to begin.</p>
            @elseif (! $activeScope)
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Before any network probes or tests begin, the tester must confirm authorization and configure scope limits.
                </p>
                <a href="{{ route('scope.edit', $project) }}" class="btn btn-primary">Define & Confirm Authorization Scope →</a>
            @elseif ($project->current_step === 0)
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Scope confirmed! You are now ready to run Step 1: Reconnaissance (safe DNS and HTTP information gathering).
                </p>
                <a href="{{ route('recon.show', $project) }}" class="btn btn-primary">Launch Step 1: Reconnaissance →</a>
            @elseif ($project->current_step === 1)
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Reconnaissance complete! Proceed to Step 2 to perform the security assessment and automated finding checks.
                </p>
                <a href="{{ route('scan.show', $project) }}" class="btn btn-primary">Launch Step 2: Security Scan →</a>
            @elseif ($project->current_step === 2)
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Security assessment complete! Proceed to Step 3 for controlled load testing with safety thresholds and circuit breakers.
                </p>
                <a href="{{ route('load-test.show', $project) }}" class="btn btn-primary">Launch Step 3: Controlled Load Test →</a>
            @elseif ($project->current_step === 3)
                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                    Controlled load test complete! Proceed to Step 4 to review findings, validate vulnerabilities, and export the official PDF assessment report.
                </p>
                <a href="{{ route('report.show', $project) }}" class="btn btn-primary">Go to Step 4: Final Report →</a>
            @else
                <p style="color: #34d399; margin-bottom: 1rem;">
                    ✓ All assessment steps completed! The final assessment report has been generated.
                </p>
                <a href="{{ route('report.show', $project) }}" class="btn btn-secondary">View Final Report</a>
            @endif
        </div>
    </div>

    <!-- Right Column: Scope & Safety Sidebar -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1rem;">Scope Authorization</h3>
                @if ($activeScope)
                    <span class="badge badge-low">Authorized</span>
                @else
                    <span class="badge badge-critical">Unconfirmed</span>
                @endif
            </div>

            @if ($activeScope)
                <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Allowed Domains:</strong><br>
                        @foreach ($activeScope->allowed_domains ?? [] as $dom)
                            <span class="badge badge-info font-mono" style="margin-right: 0.25rem;">{{ $dom }}</span>
                        @endforeach
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Window:</strong><br>
                        {{ $activeScope->window_start->format('M d, H:i') }} &ndash; {{ $activeScope->window_end->format('M d, H:i') }}
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Safety Ceilings:</strong><br>
                        Rate: {{ $activeScope->effectiveMaxRequestRate() }} req/s &bull; Concurrency: {{ $activeScope->effectiveMaxConcurrency() }} &bull; Max: {{ $activeScope->effectiveMaxTotalRequests() }} reqs
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="{{ route('scope.edit', $project) }}" style="color: var(--primary); font-size: 0.8rem;">Edit Authorization Scope &rarr;</a>
                    </div>
                </div>
            @else
                <p style="font-size: 0.85rem; color: #f87171; margin-bottom: 1rem;">
                    No active scope window. Probes cannot run until authorization is recorded.
                </p>
                <a href="{{ route('scope.edit', $project) }}" class="btn btn-primary btn-sm" style="width: 100%;">Set Scope Now</a>
            @endif
        </div>

        <!-- Audit & Findings Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: 1rem;">Findings Summary</h3>
                <span class="badge badge-info">{{ $project->findings->count() }} Total</span>
            </div>
            <div style="font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.35rem 0;">
                    <span style="color: #f87171;">Critical</span>
                    <strong>{{ $project->findings->where('severity', 'critical')->count() }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.35rem 0;">
                    <span style="color: #fb923c;">High</span>
                    <strong>{{ $project->findings->where('severity', 'high')->count() }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.35rem 0;">
                    <span style="color: #fbbf24;">Medium</span>
                    <strong>{{ $project->findings->where('severity', 'medium')->count() }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.35rem 0;">
                    <span style="color: #60a5fa;">Low</span>
                    <strong>{{ $project->findings->where('severity', 'low')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
