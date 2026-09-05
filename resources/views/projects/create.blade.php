@extends('layouts.app', ['title' => 'New Assessment Project'])

@section('content')
<div style="max-width: 650px; margin: 1rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Initialize Assessment Project</h1>
            <span class="badge badge-info">Step 0: Setup</span>
        </div>

        <form action="{{ route('projects.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Project Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Q3 Security Audit - Production Web App">
            </div>

            <div class="form-group">
                <label for="client_name" class="form-label">Client / Organization</label>
                <input type="text" id="client_name" name="client_name" value="{{ old('client_name') }}" class="form-input" placeholder="e.g. Acme Corp Enterprise">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Assessment Description & Objective</label>
                <textarea id="description" name="description" rows="3" class="form-textarea" placeholder="Brief scope description, goals, compliance requirements...">{{ old('description') }}</textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Project & Set Scope →</button>
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
