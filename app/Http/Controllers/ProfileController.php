<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id]]);
        $request->user()->update($validated);
        AuditLog::record('profile.updated', 'success', $request->user()->id);
        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()]]);
        $request->user()->update(['password' => Hash::make($validated['password'])]);
        $request->session()->regenerate();
        AuditLog::record('profile.password_changed', 'success', $request->user()->id);
        return back()->with('success', 'Password changed successfully.');
    }
}
