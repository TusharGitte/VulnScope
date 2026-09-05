@extends('layouts.app', ['title' => 'Projects'])

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Projects</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">All active and past penetration testing assessments</p>
    </div>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Project</a>
</div>

<div class="card">
    @if ($projects->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <p style="margin-bottom: 1rem;">No assessment projects found.</p>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">Create First Project</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Stage</th>
                        <th>Created</th>
                        <th style="text-align: right; width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $project) }}" style="color: #fff; font-weight: 600; text-decoration: none;">
                                    {{ $project->name }}
                                </a>
                            </td>
                            <td>{{ $project->client_name ?? '—' }}</td>
                            <td class="font-mono" style="color: var(--accent-cyan);">
                                {{ $project->targets->first()?->hostname ?? '—' }}
                            </td>
                            <td><span class="badge badge-info">{{ ucfirst($project->status) }}</span></td>
                            <td>Step {{ $project->current_step }} / 4</td>
                            <td>{{ $project->created_at->format('M d, Y') }}</td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="View Project">
                                        View
                                    </a>
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem;" title="Edit Project Details">
                                        Edit
                                    </a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete project \'{{ addslashes($project->name) }}\' and all associated assessment data?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; color: #f87171;" title="Delete Project">
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
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
