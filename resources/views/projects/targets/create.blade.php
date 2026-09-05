@extends('layouts.app', ['title' => 'Add Target'])

@section('content')
<div style="max-width: 600px; margin: 2rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Add Assessment Target</h1>
            <span class="badge badge-info">{{ $project->name }}</span>
        </div>

        <form action="{{ route('targets.store', $project) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="input_url" class="form-label">Target URL *</label>
                <input type="url" id="input_url" name="input_url" value="{{ old('input_url') }}" required class="form-input" placeholder="https://app.example.com">
                <small style="color: var(--text-muted); font-size: 0.8rem;">Include scheme (http/https). The backend will automatically extract and normalize the hostname.</small>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Target</button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
