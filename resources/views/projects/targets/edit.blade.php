@extends('layouts.app', ['title' => 'Edit Target — ' . $target->hostname])

@section('content')
<div style="max-width: 650px; margin: 1rem auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Edit Target Host</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Target: <code class="font-mono" style="color: var(--accent-cyan);">{{ $target->hostname }}</code>
            </p>
        </div>
        <a href="{{ route('projects.show', $target->project_id) }}" class="btn btn-secondary btn-sm">&larr; Project Overview</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Target Configuration</h2>
            <span class="badge badge-info">ID #{{ $target->id }}</span>
        </div>

        <form action="{{ route('targets.update', [$target->project_id, $target]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="input_url" class="form-label">Target URL *</label>
                <input type="url" id="input_url" name="input_url" value="{{ old('input_url', $target->input_url) }}" required class="form-input">
                @error('input_url') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status *</label>
                <select id="status" name="status" required class="form-select">
                    <option value="pending" {{ old('status', $target->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="active" {{ old('status', $target->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="blocked" {{ old('status', $target->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                    <option value="retired" {{ old('status', $target->status) === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Update Target</button>
                <a href="{{ route('projects.show', $target->project_id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Danger Zone: Delete Target -->
    <div class="card" style="border-left: 4px solid var(--danger); margin-top: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.05rem; color: #f87171; margin-bottom: 0.25rem;">Remove Target</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Remove this endpoint and its associated scan results.</p>
            </div>
            <form action="{{ route('targets.destroy', [$target->project_id, $target]) }}" method="POST" onsubmit="return confirm('Permanently remove this target?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="background: var(--danger); color: #fff; padding: 0.5rem 1rem;">Remove Target</button>
            </form>
        </div>
    </div>
</div>
@endsection
