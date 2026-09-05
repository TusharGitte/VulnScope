@extends('layouts.app', ['title' => 'Scope & Authorization Setup'])

@section('content')
<div style="max-width: 800px; margin: 1rem auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h1 class="card-title">Scope & Authorization Configuration</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                    Project: <strong style="color: #fff;">{{ $project->name }}</strong>
                </p>
            </div>
            <span class="badge badge-warning">Enforcement Mandatory</span>
        </div>

        <form action="{{ route('scope.store', $project) }}" method="POST">
            @csrf

            <!-- Target Domain Rules -->
            <div class="card" style="background: var(--bg-secondary); margin-bottom: 1.5rem;">
                <h2 style="font-size: 1rem; color: #fff; margin-bottom: 1rem;">1. Target Whitelist & Exclusions</h2>

                @php
                    $defaultDomain = $project->targets->first()?->hostname ?? 'example.com';
                    $existingDomains = $rule ? implode("\n", $rule->allowed_domains ?? []) : $defaultDomain;
                    $existingIps = $rule ? implode("\n", $rule->allowed_ip_ranges ?? []) : '';
                    $existingExcluded = $rule ? implode("\n", $rule->excluded_hosts ?? []) : '';
                    $existingPorts = $rule ? implode(', ', $rule->allowed_ports ?? []) : '80, 443';
                    $existingEndpoints = $rule ? implode("\n", $rule->allowed_endpoints ?? []) : '';
                @endphp

                <div class="form-group">
                    <label for="allowed_domains" class="form-label">Allowed Domains & Hostnames * (one per line or comma-separated)</label>
                    <textarea id="allowed_domains" name="allowed_domains" rows="3" required class="form-textarea font-mono" placeholder="example.com&#10;api.example.com">{{ old('allowed_domains', $existingDomains) }}</textarea>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Only hosts listed here will be contacted by the scanner or load tester.</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="allowed_ports" class="form-label">Allowed Ports</label>
                        <input type="text" id="allowed_ports" name="allowed_ports" required value="{{ old('allowed_ports', $existingPorts) }}" class="form-input font-mono" placeholder="80, 443, 8080">
                    </div>
                    <div class="form-group">
                        <label for="excluded_hosts" class="form-label">Excluded Hosts / IPs</label>
                        <input type="text" id="excluded_hosts" name="excluded_hosts" value="{{ old('excluded_hosts', $existingExcluded) }}" class="form-input font-mono" placeholder="admin.example.com, 10.0.0.1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="allowed_endpoints" class="form-label">Allowed Endpoints / Paths (optional)</label>
                    <textarea id="allowed_endpoints" name="allowed_endpoints" rows=2 class="form-textarea font-mono" placeholder="/api/*\nhttps://example.com/login">{{ old('allowed_endpoints', $existingEndpoints) }}</textarea>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Optional path or full-URL glob patterns. Leave empty to permit all paths on allowed hosts.</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="allowed_ip_ranges" class="form-label">Allowed CIDR Ranges (optional)</label>
                    <input type="text" id="allowed_ip_ranges" name="allowed_ip_ranges" value="{{ old('allowed_ip_ranges', $existingIps) }}" class="form-input font-mono" placeholder="192.168.1.0/24">
                </div>
            </div>

            <!-- Testing Window -->
            <div class="card" style="background: var(--bg-secondary); margin-bottom: 1.5rem;">
                <h2 style="font-size: 1rem; color: #fff; margin-bottom: 1rem;">2. Authorized Time Window</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="window_start" class="form-label">Start Time *</label>
                        <input type="datetime-local" id="window_start" name="window_start" required
                               value="{{ old('window_start', ($rule?->window_start ?? now())->format('Y-m-d\TH:i')) }}"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="window_end" class="form-label">End Time *</label>
                        <input type="datetime-local" id="window_end" name="window_end" required
                               value="{{ old('window_end', ($rule?->window_end ?? now()->addDays(7))->format('Y-m-d\TH:i')) }}"
                               class="form-input">
                    </div>
                </div>
            </div>

            <!-- Hard Technical Ceilings -->
            <div class="card" style="background: var(--bg-secondary); margin-bottom: 1.5rem;">
                <h2 style="font-size: 1rem; color: #fff; margin-bottom: 1rem;">3. Technical Rate & Safety Ceilings</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Platform-wide ceiling limits are enforced in the backend: Max RPS: {{ config('vapt.max_request_rate') }}, Max Concurrency: {{ config('vapt.max_concurrency') }}.
                </p>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label for="max_request_rate" class="form-label">Max Rate (RPS) *</label>
                        <input type="number" id="max_request_rate" name="max_request_rate" min="1" max="{{ config('vapt.max_request_rate') }}"
                               value="{{ old('max_request_rate', $rule?->max_request_rate ?? 5) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="max_concurrency" class="form-label">Max Concurrency *</label>
                        <input type="number" id="max_concurrency" name="max_concurrency" min="1" max="{{ config('vapt.max_concurrency') }}"
                               value="{{ old('max_concurrency', $rule?->max_concurrency ?? 5) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="max_duration_seconds" class="form-label">Duration (sec) *</label>
                        <input type="number" id="max_duration_seconds" name="max_duration_seconds" min="10" max="{{ config('vapt.max_duration_seconds') }}"
                               value="{{ old('max_duration_seconds', $rule?->max_duration_seconds ?? 300) }}" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="max_total_requests" class="form-label">Max Requests *</label>
                        <input type="number" id="max_total_requests" name="max_total_requests" min="10" max="{{ config('vapt.max_total_requests') }}"
                               value="{{ old('max_total_requests', $rule?->max_total_requests ?? 5000) }}" required class="form-input">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 0.5rem;">
                    <label style="display:flex;gap:.5rem;align-items:flex-start;color:#fff;font-size:.85rem;">
                        <input type="checkbox" name="authenticated_testing_allowed" value="1" {{ old('authenticated_testing_allowed', $rule?->authenticated_testing_allowed ?? false) ? 'checked' : '' }}>
                        <span><strong>Allow authenticated testing</strong><br><small style="color:var(--text-muted);">Enables analyst use of explicitly authorized test identities. The platform never attempts to obtain credentials.</small></span>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 0.5rem; margin-bottom: 0;">
                    <label for="authorization_notes" class="form-label">Authorization Notes & Reference</label>
                    <input type="text" id="authorization_notes" name="authorization_notes"
                           value="{{ old('authorization_notes', $rule?->authorization_notes) }}"
                           class="form-input" placeholder="e.g. Engagement ROE document #ROE-2026-081 signed by CISO">
                </div>
            </div>

            <!-- Confirmation Checkbox -->
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <input type="checkbox" id="explicit_authorization_confirm" name="explicit_authorization_confirm" value="1" required style="margin-top: 0.25rem;">
                    <label for="explicit_authorization_confirm" style="font-size: 0.875rem; color: #fff; cursor: pointer; line-height: 1.5;">
                        <strong>Legal Authorization Attestation:</strong> I hereby certify that I have explicit, written authorization from the system owner to conduct security assessment, reconnaissance, and controlled testing against the targets specified above. I understand all actions will be permanently logged.
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Confirm Authorization & Unlock Step 1 →</button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
