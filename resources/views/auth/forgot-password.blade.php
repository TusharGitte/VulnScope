@extends('layouts.app', ['title' => 'Reset Password'])
@section('content')
<div style="max-width:440px;margin:3rem auto"><div class="card"><h1 class="card-title">Reset Password</h1><p style="color:var(--text-muted);margin:1rem 0">Enter your email and we will send a password-reset link when the account exists.</p><form method="POST" action="{{ route('password.email') }}">@csrf<div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="{{ old('email') }}" required></div><button class="btn btn-primary" style="width:100%">Send Reset Link</button></form><p style="margin-top:1rem;text-align:center"><a href="{{ route('login') }}">Back to login</a></p></div></div>
@endsection
