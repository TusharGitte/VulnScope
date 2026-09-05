<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\SecretRedactor;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'project_id', 'target', 'action', 'result', 'context', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Central logging entry point. Always redact secrets in $context before calling this
     * (see App\Services\SecretRedactor) — never pass raw request bodies/tokens/passwords.
     */
    public static function record(
        string $action,
        string $result = 'success',
        ?int $userId = null,
        ?int $projectId = null,
        string|array|null $target = null,
        array $context = [],
    ): self {
        if (is_array($target)) {
            $context = array_merge($target, $context);
            $target = null;
        }

        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'project_id' => $projectId,
            'target' => $target,
            'action' => $action,
            'result' => $result,
            'context' => SecretRedactor::redactArray($context),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
