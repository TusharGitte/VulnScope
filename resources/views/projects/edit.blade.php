@extends('layouts.app', ['title' => 'Edit Project'])

@section('content')
<div style="max-width: 650px; margin: 1rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Edit Project Details</h1>
            <span class="badge badge-info">Settings</span>
        </div>

        <form action="{{ route('projects.update', $project) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name" class="form-label">Project Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required class="form-input">
            </div>

            <div class="form-group">
                <label for="client_name" class="form-label">Client / Organization</label>
                <input type="text" id="client_name" name="client_name" value="{{ old('client_name', $project->client_name) }}" class="form-input">
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status *</label>
                <select id="status" name="status" class="form-select">
                    <option value="draft" {{ $project->status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ $project->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="archived" {{ $project->status === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="3" class="form-textarea">{{ old('description', $project->description) }}</textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Danger Zone: Delete Project -->
    <div class="card" style="border-left: 4px solid var(--danger); margin-top: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.05rem; color: #f87171; margin-bottom: 0.25rem;">Delete Project</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Permanently remove this project and all testing artifacts.</p>
            </div>
            <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete project \'{{ addslashes($project->name) }}\'? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="background: var(--danger); color: #fff; padding: 0.5rem 1rem;">Delete Project</button>
            </form>
        </div>
    </div>
</div>
@endsection
