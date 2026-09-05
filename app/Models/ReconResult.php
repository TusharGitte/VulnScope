<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconResult extends Model
{
    use HasFactory;

    public const SECTIONS = [
        'overview', 'network', 'dns', 'tls', 'hosting',
        'tech_stack', 'http', 'endpoints', 'headers', 'historical',
    ];

    protected $fillable = [
        'scan_run_id', 'target_id', 'section', 'key', 'value', 'confidence', 'source',
    ];

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }
}
