@extends('layouts.app', ['title' => 'Step Locked'])

@section('content')
<div style="max-width: 600px; margin: 4rem auto; text-align: center;">
    <div class="card" style="border-color: rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.05);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
        <h1 style="font-size: 1.5rem; color: #fbbf24; margin-bottom: 1rem;">This Step Isn't Unlocked Yet</h1>
        <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 1.5rem;">
            {{ $exception->getMessage() ?: 'You don\'t have permission to view this page, or the prior workflow step hasn\'t been completed yet.' }}
        </p>
        <div style="background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted); text-align: left; margin-bottom: 1.5rem;">
            <strong>Why am I seeing this?</strong> Steps must be completed in order — Recon → Scan → Load Test → Report.
            If a step shows "queued" or "running" and never finishes, make sure the background queue worker is running
            (<code>php artisan queue:work database --queue=vapt --sleep=1 --tries=1 --timeout=1800</code>, or <code>./run-worker.sh</code>).
            Without it, queued jobs will sit untouched indefinitely.
        </div>
        <div>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Go Back</a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
        </div>
    </div>
</div>
@endsection
