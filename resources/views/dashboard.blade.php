@extends('layouts.app', ['title' => 'Security Analyst Dashboard'])

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Assessment Operations</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage authorized VAPT projects, targets, and security reports</p>
    </div>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Assessment Project</a>
</div>

<!-- KPI Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem;">
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Projects</div>
        <div style="font-size: 2rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">{{ $stats['total_projects'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Active Scans</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--accent-cyan); margin-top: 0.25rem;">{{ $stats['active_scans'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Findings</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-top: 0.25rem;">{{ $stats['total_findings'] }}</div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Critical / High</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--danger); margin-top: 0.25rem;">{{ $stats['critical_high_findings'] }}</div>
    </div>
</div>

<!-- Recent Projects Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Projects & Assessments</h2>
        <a href="{{ route('projects.create') }}" class="btn btn-secondary btn-sm">+ Add Project</a>
    </div>

    @if ($projects->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📁</div>
            <p style="font-size: 1rem; margin-bottom: 1rem;">No assessment projects yet.</p>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">Create Your First Project</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Client / Organization</th>
                        <th>Target</th>
                        <th>Stage Progression</th>
                        <th>Status</th>
                        <th>Findings</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $project)
                        @php
                            $target = $project->targets->first();
                            $stepLabels = [
                                0 => 'Scope Setup',
                                1 => 'Step 1: Recon',
                                2 => 'Step 2: Scan',
                                3 => 'Step 3: Load Test',
                                4 => 'Step 4: Report'
                            ];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $project) }}" style="color: #fff; font-weight: 600; text-decoration: none;">
                                    {{ $project->name }}
                                </a>
                            </td>
                            <td>{{ $project->client_name ?? 'Internal' }}</td>
                            <td>
                                @if ($target)
                                    <span class="font-mono" style="color: var(--accent-cyan);">{{ $target->hostname }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-style: italic;">No target configured</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $project->current_step > 0 ? 'badge-info' : 'badge-low' }}">
                                    {{ $stepLabels[$project->current_step] ?? 'Stage ' . $project->current_step }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $project->status === 'completed' ? 'badge-low' : 'badge-medium' }}">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: {{ $project->findings->count() > 0 ? 'var(--danger)' : 'var(--text-muted)' }};">
                                    {{ $project->findings->count() }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm">Open Project →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
