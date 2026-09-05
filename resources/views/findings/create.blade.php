@extends('layouts.app', ['title' => 'Register Finding — ' . $project->name])

@section('content')
<div style="max-width: 800px; margin: 1rem auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 4: Findings</div>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">Register Vulnerability Finding</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a>
            </p>
        </div>
        <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary btn-sm">&larr; Back to Findings</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Vulnerability Finding Details</h2>
            <span class="badge badge-info">Manual Finding Entry</span>
        </div>

        <form action="{{ route('findings.store', $project) }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="title" class="form-label">Finding Title *</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required class="form-input" placeholder="e.g. Blind SQL Injection in /api/v1/auth endpoint">
                @error('title') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="severity" class="form-label">Severity Level *</label>
                    <select id="severity" name="severity" required class="form-select">
                        <option value="critical" {{ old('severity') === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                        <option value="high" {{ old('severity') === 'high' ? 'selected' : '' }}>🟠 High</option>
                        <option value="medium" {{ old('severity', 'medium') === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                        <option value="low" {{ old('severity') === 'low' ? 'selected' : '' }}>🔵 Low</option>
                        <option value="informational" {{ old('severity') === 'informational' ? 'selected' : '' }}>⚪ Informational</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="confidence" class="form-label">Confidence *</label>
                    <select id="confidence" name="confidence" required class="form-select">
                        <option value="low" {{ old('confidence') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('confidence', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('confidence') === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="form-label">Triage Status *</label>
                    <select id="status" name="status" required class="form-select">
                        <option value="new" {{ old('status', 'confirmed') === 'new' ? 'selected' : '' }}>New</option>
                        <option value="needs_review" {{ old('status') === 'needs_review' ? 'selected' : '' }}>Needs Review</option>
                        <option value="confirmed" {{ old('status', 'confirmed') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="false_positive" {{ old('status') === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                        <option value="risk_accepted" {{ old('status') === 'risk_accepted' ? 'selected' : '' }}>Risk Accepted</option>
                        <option value="fixed" {{ old('status') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="reopened" {{ old('status') === 'reopened' ? 'selected' : '' }}>Reopened</option>
                        <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">OWASP / Vulnerability Category *</label>
                    <input type="text" id="category" name="category" value="{{ old('category', 'Injection') }}" required class="form-input" placeholder="e.g. Broken Access Control, Injection, Misconfiguration">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="target_id" class="form-label">Target Host</label>
                    <select id="target_id" name="target_id" class="form-select">
                        @foreach ($targets as $target)
                            <option value="{{ $target->id }}">{{ $target->hostname }} ({{ $target->normalized_url }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="url" class="form-label">Affected URL / Endpoint</label>
                    <input type="text" id="url" name="url" value="{{ old('url', $project->targets->first()?->normalized_url) }}" class="form-input" placeholder="https://target.com/path">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="parameter" class="form-label">Vulnerable Parameter</label>
                    <input type="text" id="parameter" name="parameter" value="{{ old('parameter') }}" class="form-input" placeholder="e.g. id, token, query">
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description & Technical Overview *</label>
                <textarea id="description" name="description" rows="3" required class="form-textarea" placeholder="Detailed description of the discovered flaw...">{{ old('description') }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="impact" class="form-label">Security Impact</label>
                    <textarea id="impact" name="impact" rows="3" class="form-textarea" placeholder="Consequences of exploitation (e.g. Remote Code Execution, Privilege Escalation)...">{{ old('impact') }}</textarea>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="remediation" class="form-label">Remediation Guidance</label>
                    <textarea id="remediation" name="remediation" rows="3" class="form-textarea" placeholder="Actionable developer fix or mitigation...">{{ old('remediation') }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="reproduction_guidance" class="form-label">Reproduction Steps / Proof of Concept</label>
                <textarea id="reproduction_guidance" name="reproduction_guidance" rows="3" class="form-textarea font-mono" style="font-size: 0.85rem;" placeholder="1. Send GET request to /endpoint?query=&#10;2. Observe reflected payload in response body...">{{ old('reproduction_guidance') }}</textarea>
            </div>

            <div class="form-group">
                <label for="references" class="form-label">References (one URL per line)</label>
                <textarea id="references" name="references_text" rows="3" class="form-textarea font-mono" placeholder="https://owasp.org/
https://cwe.mitre.org/">{{ old('references_text') }}</textarea>
            </div>

            <div class="form-group">
                <label for="analyst_notes" class="form-label">Analyst Triage Notes</label>
                <textarea id="analyst_notes" name="analyst_notes" rows="2" class="form-textarea" placeholder="Internal analyst assessment notes...">{{ old('analyst_notes') }}</textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">+ Register Vulnerability Finding</button>
                <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
