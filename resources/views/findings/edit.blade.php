@extends('layouts.app', ['title' => 'Edit Finding — ' . $finding->title])

@section('content')
<div style="max-width: 800px; margin: 1rem auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 4: Findings</div>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Edit Vulnerability Finding</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('findings.show', [$project, $finding]) }}" class="btn btn-secondary btn-sm">View Finding</a>
            <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary btn-sm">&larr; All Findings</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Modify Finding Attributes</h2>
            <span class="badge badge-info">ID #{{ $finding->id }}</span>
        </div>

        <form action="{{ route('findings.update', [$project, $finding]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title" class="form-label">Finding Title *</label>
                <input type="text" id="title" name="title" value="{{ old('title', $finding->title) }}" required class="form-input">
                @error('title') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="severity" class="form-label">Severity Level *</label>
                    <select id="severity" name="severity" required class="form-select">
                        <option value="critical" {{ old('severity', $finding->severity) === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                        <option value="high" {{ old('severity', $finding->severity) === 'high' ? 'selected' : '' }}>🟠 High</option>
                        <option value="medium" {{ old('severity', $finding->severity) === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                        <option value="low" {{ old('severity', $finding->severity) === 'low' ? 'selected' : '' }}>🔵 Low</option>
                        <option value="informational" {{ old('severity', $finding->severity) === 'informational' ? 'selected' : '' }}>⚪ Informational</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="confidence" class="form-label">Confidence *</label>
                    <select id="confidence" name="confidence" required class="form-select">
                        <option value="low" {{ old('confidence', $finding->confidence) === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('confidence', $finding->confidence) === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('confidence', $finding->confidence) === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="form-label">Triage Status *</label>
                    <select id="status" name="status" required class="form-select">
                        <option value="new" {{ old('status', $finding->status) === 'new' ? 'selected' : '' }}>New</option>
                        <option value="needs_review" {{ old('status', $finding->status) === 'needs_review' ? 'selected' : '' }}>Needs Review</option>
                        <option value="confirmed" {{ old('status', $finding->status) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="false_positive" {{ old('status', $finding->status) === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                        <option value="risk_accepted" {{ old('status', $finding->status) === 'risk_accepted' ? 'selected' : '' }}>Risk Accepted</option>
                        <option value="fixed" {{ old('status', $finding->status) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="reopened" {{ old('status', $finding->status) === 'reopened' ? 'selected' : '' }}>Reopened</option>
                        <option value="closed" {{ old('status', $finding->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">OWASP / Vulnerability Category *</label>
                    <input type="text" id="category" name="category" value="{{ old('category', $finding->category) }}" required class="form-input">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="target_id" class="form-label">Target Host</label>
                    <select id="target_id" name="target_id" class="form-select">
                        @foreach ($targets as $target)
                            <option value="{{ $target->id }}" {{ old('target_id', $finding->target_id) == $target->id ? 'selected' : '' }}>
                                {{ $target->hostname }} ({{ $target->normalized_url }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="url" class="form-label">Affected URL / Endpoint</label>
                    <input type="text" id="url" name="url" value="{{ old('url', $finding->url) }}" class="form-input">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="parameter" class="form-label">Vulnerable Parameter</label>
                    <input type="text" id="parameter" name="parameter" value="{{ old('parameter', $finding->parameter) }}" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description & Technical Overview *</label>
                <textarea id="description" name="description" rows="3" required class="form-textarea">{{ old('description', $finding->description) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="impact" class="form-label">Security Impact</label>
                    <textarea id="impact" name="impact" rows="3" class="form-textarea">{{ old('impact', $finding->impact) }}</textarea>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="remediation" class="form-label">Remediation Guidance</label>
                    <textarea id="remediation" name="remediation" rows="3" class="form-textarea">{{ old('remediation', $finding->remediation) }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="reproduction_guidance" class="form-label">Reproduction Steps / Proof of Concept</label>
                <textarea id="reproduction_guidance" name="reproduction_guidance" rows="3" class="form-textarea font-mono" style="font-size: 0.85rem;">{{ old('reproduction_guidance', $finding->reproduction_guidance) }}</textarea>
            </div>

            <div class="form-group">
                <label for="references" class="form-label">References (one URL per line)</label>
                <textarea id="references" name="references_text" rows="3" class="form-textarea font-mono" placeholder="https://owasp.org/
https://cwe.mitre.org/">{{ old('references_text', implode("\n", $finding->references ?? [])) }}</textarea>
                <input type="hidden" name="references[]" value="">
                <p style="color: var(--text-muted); font-size: 0.78rem; margin-top: 0.35rem;">These references are optional. Keep secrets and session data out of evidence and references.</p>
            </div>

            <div class="form-group">
                <label for="analyst_notes" class="form-label">Analyst Triage Notes</label>
                <textarea id="analyst_notes" name="analyst_notes" rows="2" class="form-textarea">{{ old('analyst_notes', $finding->analyst_notes) }}</textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                <a href="{{ route('findings.show', [$project, $finding]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Danger Zone: Delete Finding -->
    <div class="card" style="border-left: 4px solid var(--danger); margin-top: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.1rem; color: #f87171; margin-bottom: 0.25rem;">Delete Vulnerability Finding</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Permanently remove this finding and its audit record from the assessment project.</p>
            </div>
            <form action="{{ route('findings.destroy', [$project, $finding]) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this finding?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="background: var(--danger); color: #fff; padding: 0.5rem 1rem;">Delete Finding</button>
            </form>
        </div>
    </div>
</div>
@endsection
