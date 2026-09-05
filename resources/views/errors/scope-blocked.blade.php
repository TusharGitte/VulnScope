@extends('layouts.app', ['title' => 'Scope Violation — Request Blocked'])

@section('content')
<div style="max-width: 600px; margin: 4rem auto; text-align: center;">
    <div class="card" style="border-color: rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.05);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🛡️ ⛔</div>
        <h1 style="font-size: 1.5rem; color: #f87171; margin-bottom: 1rem;">Action Blocked by Scope Enforcement</h1>
        <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 1.5rem;">
            {{ $message ?? 'The requested operation was blocked because it violates the authorized scope boundaries.' }}
        </p>
        <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted); text-align: left; margin-bottom: 1.5rem;">
            <strong>Rule:</strong> The VAPT backend enforces domain whitelists, excluded hosts, IP ranges, port allowlists, and authorized assessment time windows. Any out-of-scope probe is instantly dropped and logged to <code>audit_logs</code>.
        </div>
        <div>
            <a href="javascript:history.back()" class="btn btn-secondary">Return to Project</a>
        </div>
    </div>
</div>
@endsection
