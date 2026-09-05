@extends('layouts.app', ['title' => 'Sign In'])

@section('content')
<div style="max-width: 440px; margin: 3rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Analyst Authentication</h1>
            <span class="badge badge-info">Secure Access</span>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="form-input" placeholder="analyst@secops.local">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required class="form-input" placeholder="••••••••••••">
            </div>

            <div style="text-align:right; margin-bottom:1rem;"><a href="{{ route('password.request') }}" style="color:var(--primary);font-size:.85rem">Forgot password?</a></div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" style="font-size: 0.85rem; color: var(--text-muted); cursor: pointer;">Remember this session</label>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </div>
        </form>

        <div style="margin-top: 1.25rem; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
            Don't have an analyst account? <a href="{{ route('register') }}" style="color: var(--primary);">Register here</a>
        </div>
    </div>
</div>
@endsection
