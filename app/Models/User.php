<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // 'role', 'is_active', and 'email_verified_at' are deliberately NOT mass-assignable.
    // These are privilege/trust fields — if a future form or request ever does
    // User::create($request->all()) or $user->update($request->all()), an attacker
    // could otherwise set their own role to 'admin' or mark themselves verified/active.
    // Set them explicitly with forceFill()/direct attribute assignment instead.

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'owner_id');
    }
}
