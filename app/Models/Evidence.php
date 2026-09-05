<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_id', 'type', 'storage_path', 'content', 'secrets_redacted', 'captured_by',
    ];

    protected function casts(): array
    {
        return [
            'secrets_redacted' => 'boolean',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(VulnerabilityFinding::class, 'finding_id');
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
