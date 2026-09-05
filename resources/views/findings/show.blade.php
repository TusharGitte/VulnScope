@extends('layouts.app', ['title' => $finding->title . ' — Findings'])

@section('content')
<div style="max-width: 900px; margin: 1rem auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Step 4: Findings Triage</div>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #fff;">{{ $finding->title }}</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Project: <a href="{{ route('projects.show', $project) }}" style="color: #fff;">{{ $project->name }}</a>
                &bull; Target: <code class="font-mono" style="color: var(--accent-cyan);">{{ $finding->url }}</code>
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('findings.edit', [$project, $finding]) }}" class="btn btn-primary btn-sm">✎ Edit Finding</a>
            <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary btn-sm">&larr; Back to Findings</a>
        </div>
    </div>

    @php
        $sevBadges = [
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'medium' => 'badge-medium',
            'low' => 'badge-low',
            'informational' => 'badge-info',
        ];
    @endphp

    <!-- Finding Overview Card -->
    <div class="card" style="border-left: 4px solid {{ $finding->severity === 'critical' ? '#ef4444' : ($finding->severity === 'high' ? '#f97316' : ($finding->severity === 'medium' ? '#f59e0b' : '#3b82f6')) }};">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem;">
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <span class="badge {{ $sevBadges[$finding->severity] ?? 'badge-info' }}" style="font-size: 0.9rem; padding: 0.35rem 0.75rem;">
                    {{ strtoupper($finding->severity) }}
                </span>
                <span class="badge badge-info" style="font-size: 0.85rem;">{{ $finding->category }}</span>
                <span class="badge badge-low" style="font-size: 0.85rem;">Status: {{ ucfirst($finding->status) }}</span>
                <span class="badge badge-info" style="font-size: 0.85rem;">Confidence: {{ ucfirst($finding->confidence) }}</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                First seen: {{ $finding->first_seen_at?->format('M d, Y H:i') ?? 'N/A' }}
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Endpoint / Scope</h3>
            <div style="background: var(--bg-secondary); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <span class="font-mono" style="color: var(--accent-cyan); font-size: 0.9rem;">{{ $finding->url }}</span>
                @if ($finding->parameter)
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Param: <code class="font-mono" style="color: #fbbf24;">{{ $finding->parameter }}</code></span>
                @endif
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Description</h3>
            <div style="color: var(--text-main); font-size: 0.95rem; line-height: 1.6; white-space: pre-line;">{{ $finding->description }}</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <h3 style="font-size: 0.85rem; color: #f87171; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Potential Impact</h3>
                <p style="color: var(--text-main); font-size: 0.9rem; line-height: 1.5;">{{ $finding->impact ?: 'No impact analysis documented.' }}</p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <h3 style="font-size: 0.85rem; color: #34d399; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Remediation & Fix</h3>
                <p style="color: var(--text-main); font-size: 0.9rem; line-height: 1.5;">{{ $finding->remediation ?: 'No remediation steps specified.' }}</p>
            </div>
        </div>

        @if ($finding->reproduction_guidance)
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Reproduction Guidance / PoC</h3>
                <pre style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--accent-cyan); font-size: 0.85rem; overflow-x: auto; white-space: pre-wrap;">{{ $finding->reproduction_guidance }}</pre>
            </div>
        @endif

        @if (!empty($finding->references))
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">References</h3>
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($finding->references as $reference)
                        <li style="margin-bottom: 0.35rem;"><a href="{{ $reference }}" target="_blank" rel="noopener noreferrer" style="color: var(--accent-cyan); word-break: break-all;">{{ $reference }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($finding->analyst_notes)
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">Analyst Assessment Notes</h3>
                <div style="background: var(--bg-secondary); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: #fbbf24; font-size: 0.9rem;">
                    {{ $finding->analyst_notes }}
                </div>
            </div>
        @endif

        <!-- Inline Triage Status Update Form -->
        <div style="border-top: 1px solid var(--border-color); padding-top: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Quick Status Update:</span>
            </div>
            <form action="{{ route('findings.update', [$project, $finding]) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                @csrf
                @method('PATCH')
                <select name="status" class="form-select" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: auto;">
                    <option value="new" {{ $finding->status === 'new' ? 'selected' : '' }}>New</option>
                    <option value="needs_review" {{ $finding->status === 'needs_review' ? 'selected' : '' }}>Needs Review</option>
                    <option value="confirmed" {{ $finding->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="false_positive" {{ $finding->status === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                    <option value="risk_accepted" {{ $finding->status === 'risk_accepted' ? 'selected' : '' }}>Risk Accepted</option>
                    <option value="fixed" {{ $finding->status === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="reopened" {{ $finding->status === 'reopened' ? 'selected' : '' }}>Reopened</option>
                    <option value="closed" {{ $finding->status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Update Status</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Evidence</h2>
            <span class="badge badge-info">{{ $finding->evidence->count() }} item(s)</span>
        </div>
        @forelse ($finding->evidence as $evidence)
            <div style="padding: 0.9rem 0; border-bottom: 1px solid var(--border-color);">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start;">
                    <div>
                        <strong style="color:#fff; text-transform:capitalize;">{{ str_replace('_', ' ', $evidence->type) }}</strong>
                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.25rem;">{{ $evidence->created_at?->format('Y-m-d H:i:s') }} · Redacted: {{ $evidence->secrets_redacted ? 'Yes' : 'No' }}</div>
                        @if ($evidence->content)
                            <pre style="margin-top:0.6rem; background:var(--bg-secondary); padding:0.75rem; border-radius:var(--radius-sm); white-space:pre-wrap; overflow:auto; max-height:260px;">{{ $evidence->content }}</pre>
                        @endif
                        @if ($evidence->storage_path)
                            <div style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);">Private file: {{ basename($evidence->storage_path) }}</div>
                        @endif
                    </div>
                    <form action="{{ route('evidence.destroy', [$project, $finding, $evidence]) }}" method="POST" onsubmit="return confirm('Delete this evidence?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:var(--text-muted);">No evidence has been attached yet.</p>
        @endforelse

        <form action="{{ route('evidence.store', [$project, $finding]) }}" method="POST" enctype="multipart/form-data" style="margin-top:1.25rem;">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label" for="evidence_type">Type *</label>
                    <select name="type" id="evidence_type" class="form-select" required>
                        @foreach(['note','screenshot','http_request','http_response','log_excerpt','file'] as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_',' ',$type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="evidence_file">File (optional)</label>
                    <input type="file" name="file" id="evidence_file" class="form-input" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="evidence_content">Redacted Evidence Text</label>
                <textarea name="content" id="evidence_content" rows="5" maxlength="50000" class="form-textarea font-mono" placeholder="Paste redacted request, response, log excerpt, or analyst note."></textarea>
            </div>
            <label style="display:flex; gap:0.5rem; align-items:flex-start; font-size:0.82rem; color:var(--text-muted);">
                <input type="checkbox" name="confirm_redacted" value="1" required> I confirm this evidence has been reviewed and secrets/session tokens have been redacted.
            </label>
            <button type="submit" class="btn btn-primary" style="margin-top:0.9rem;">Add Evidence</button>
        </form>
    </div>

    <!-- Actions & Deletion Card -->
    <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('findings.edit', [$project, $finding]) }}" class="btn btn-primary btn-sm">✎ Edit Finding Details</a>
            <a href="{{ route('findings.index', $project) }}" class="btn btn-secondary btn-sm">Back to Findings List</a>
        </div>
        <form action="{{ route('findings.destroy', [$project, $finding]) }}" method="POST" onsubmit="return confirm('Permanently delete this finding from the assessment?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" style="background: var(--danger); color: #fff; padding: 0.4rem 0.8rem;">
                🗑 Delete Finding
            </button>
        </form>
    </div>
</div>
@endsection
