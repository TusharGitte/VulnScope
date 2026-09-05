@extends('layouts.app', ['title' => 'Register Analyst'])

@section('content')
<div style="max-width: 480px; margin: 3rem auto;">
    <div class="card">
        <div class="card-header">
            <h1 class="card-title">Register Security Analyst</h1>
            <span class="badge badge-info">Authorized Personnel</span>
        </div>

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus class="form-input" placeholder="Alex Chen">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="alex.chen@secops.local">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required class="form-input" placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Repeat password">
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </div>
        </form>

        <div style="margin-top: 1.25rem; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
            Already have an account? <a href="{{ route('login') }}" style="color: var(--primary);">Sign In</a>
        </div>
    </div>
</div>
@endsection
