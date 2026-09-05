@extends('layouts.app', ['title' => 'Findings & Vulnerability Triage — ' . $project->name])

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 4: Findings</div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Vulnerability Findings & Triage</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a>
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('findings.create', $project) }}" class="btn btn-primary btn-sm">+ Add Finding</a>
        <a href="{{ route('report.show', $project) }}" class="btn btn-secondary btn-sm">&larr; Report Overview</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Identified Findings ({{ $findings->total() }})</h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <span class="badge badge-critical">{{ $counts['critical'] }} Critical</span>
            <span class="badge badge-high">{{ $counts['high'] }} High</span>
            <span class="badge badge-medium">{{ $counts['medium'] }} Medium</span>
            <span class="badge badge-low">{{ $counts['low'] }} Low</span>
            <span class="badge badge-info">{{ $counts['informational'] }} Info</span>
        </div>
    </div>

    @if ($findings->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🛡️</div>
            <p style="margin-bottom: 1rem;">No vulnerabilities registered yet for this project.</p>
            <a href="{{ route('findings.create', $project) }}" class="btn btn-primary">+ Add First Finding</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Severity</th>
                        <th>Title & Endpoint</th>
                        <th>Category</th>
                        <th style="width: 220px;">Triage Status</th>
                        <th style="text-align: right; width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sevBadges = [
                            'critical' => 'badge-critical',
                            'high' => 'badge-high',
                            'medium' => 'badge-medium',
                            'low' => 'badge-low',
                            'informational' => 'badge-info',
                        ];
                    @endphp
                    @foreach ($findings as $finding)
                        <tr>
                            <td>
                                <span class="badge {{ $sevBadges[$finding->severity] ?? 'badge-info' }}">
                                    {{ strtoupper($finding->severity) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('findings.show', [$project, $finding]) }}" style="font-weight: 600; color: #fff; text-decoration: none;">
                                    {{ $finding->title }}
                                </a>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    {{ $finding->url }}
                                    @if ($finding->parameter)
                                        &bull; <span style="color: var(--accent-cyan);">param: {{ $finding->parameter }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $finding->category }}</td>
                            <td>
                                <form action="{{ route('findings.update', [$project, $finding]) }}" method="POST" style="display: flex; gap: 0.4rem; align-items: center;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto;">
                                        <option value="new" {{ $finding->status === 'new' ? 'selected' : '' }}>New</option>
                                        <option value="confirmed" {{ $finding->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                        <option value="false_positive" {{ $finding->status === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                                        <option value="risk_accepted" {{ $finding->status === 'risk_accepted' ? 'selected' : '' }}>Risk Accepted</option>
                                        <option value="fixed" {{ $finding->status === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                        <option value="closed" {{ $finding->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Save</button>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                                    <a href="{{ route('findings.show', [$project, $finding]) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="View Details">
                                        View
                                    </a>
                                    <a href="{{ route('findings.edit', [$project, $finding]) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="Edit Finding">
                                        Edit
                                    </a>
                                    <form action="{{ route('findings.destroy', [$project, $finding]) }}" method="POST" onsubmit="return confirm('Delete this finding?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; color: #f87171;" title="Delete Finding">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $findings->links() }}
        </div>
    @endif
</div>
@endsection
