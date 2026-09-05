<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    public const STEP_NONE = 0;
    public const STEP_RECON = 1;
    public const STEP_SCAN = 2;
    public const STEP_LOAD_TEST = 3;
    public const STEP_REPORT = 4;

    protected $fillable = [
        'owner_id', 'name', 'client_name', 'description', 'status', 'current_step',
        'authorized_at', 'authorized_by',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class);
    }

    public function scopeRules(): HasMany
    {
        return $this->hasMany(ScopeRule::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(VulnerabilityFinding::class);
    }

    public function loadTests(): HasMany
    {
        return $this->hasMany(LoadTest::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Is the given workflow step (1-4) currently unlocked for this project?
     * Enforced server-side wherever a step action is triggered — never trust the frontend.
     */
    public function canEnterStep(int $step): bool
    {
        return match ($step) {
            self::STEP_RECON => $this->targets()->exists() && $this->activeScopeRule() !== null,
            self::STEP_SCAN => $this->current_step >= self::STEP_RECON,
            self::STEP_LOAD_TEST => $this->current_step >= self::STEP_SCAN,
            self::STEP_REPORT => $this->current_step >= self::STEP_LOAD_TEST,
            default => false,
        };
    }


    public function hasActiveRun(string $stage): bool
    {
        return $this->scanRuns()->where('stage', $stage)->whereIn('status', ['queued', 'running'])->exists();
    }

    public function hasAnyActiveRun(): bool
    {
        return $this->scanRuns()->whereIn('status', ['queued', 'running'])->exists();
    }

    public function activeScopeRule(): ?ScopeRule
    {
        return $this->scopeRules()
            ->where('window_start', '<=', now())
            ->where('window_end', '>=', now())
            ->latest('confirmed_at')
            ->first();
    }
}
