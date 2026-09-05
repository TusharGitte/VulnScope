@extends('layouts.app', ['title' => 'Email Verification'])

@section('content')
<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Verify Email Address</h1>
            <span class="badge badge-warning">Verification Required</span>
        </div>

        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            Before accessing the assessment workspace, please verify your email address. Use the verification link sent to your email.
        </p>

        <div style="display: flex; gap: 1rem; align-items: center;">
            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary">Resend Verification Email</button>
            </form>
        </div>
    </div>
</div>
@endsection
