@extends('layouts.app', ['title' => 'Step 4: Assessment Report — ' . $project->name])

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 4 of 4</div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">VAPT Assessment Report</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a> &bull; Client: {{ $project->client_name ?? 'Internal' }}
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary btn-sm">Triage Findings</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm">&larr; Project Overview</a>
    </div>
</div>

<!-- Assessment Executive Summary Card -->
<div class="card" style="border-left: 4px solid var(--success); margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h2 style="font-size: 1.2rem; color: #fff; margin-bottom: 0.25rem;">Executive Summary Ready for Compilation</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Steps 1 and 2 must be completed and Step 3 must be concluded (completed or safely interrupted) before a formal audit PDF report can be generated.
            </p>
        </div>
        <div>
            <form action="{{ route('report.generate', $project) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">📄 Generate Official PDF Report</button>
            </form>
        </div>
    </div>
</div>

<!-- Severity Breakdown Overview -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div class="card" style="margin-bottom: 0; text-align: center;">
        <div style="color: #f87171; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Critical</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $severitySummary['critical'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0; text-align: center;">
        <div style="color: #fb923c; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">High</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $severitySummary['high'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0; text-align: center;">
        <div style="color: #fbbf24; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Medium</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $severitySummary['medium'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0; text-align: center;">
        <div style="color: #60a5fa; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Low</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $severitySummary['low'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0; text-align: center;">
        <div style="color: #94a3b8; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Info</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $severitySummary['informational'] }}</div>
    </div>
</div>

<!-- Generated Reports List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generated Assessment Deliverables ({{ $reports->count() }})</h2>
    </div>

    @if ($reports->isEmpty())
        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
            <p style="margin-bottom: 1rem;">No reports compiled yet for this assessment.</p>
            <form action="{{ route('report.generate', $project) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Generate Initial PDF Report</button>
            </form>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Report Title</th>
                        <th>Generated On</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td style="font-weight: 600; color: #fff;">{{ $report->title }}</td>
                            <td>{{ $report->created_at->format('M d, Y H:i:s') }}</td>
                            <td><span class="badge badge-low">{{ ucfirst($report->status) }}</span></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <a href="{{ route('report.view', [$project, $report]) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                                        👁 Open in Browser
                                    </a>
                                    <a href="{{ route('report.download', [$project, $report]) }}" download="{{ preg_replace('/[^A-Za-z0-9._-]+/', '_', $report->title) . '.pdf' }}" class="btn btn-secondary btn-sm">
                                        ⬇ Download PDF
                                    </a>
                                    <form action="{{ route('report.destroy', [$project, $report]) }}" method="POST" onsubmit="return confirm('Permanently delete this generated report file?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #f87171;" title="Delete Report">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
