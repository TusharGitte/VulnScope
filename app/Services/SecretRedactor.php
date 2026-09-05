<?php

namespace App\Services;

class SecretRedactor
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'token', 'access_token', 'refresh_token',
        'authorization', 'cookie', 'set-cookie', 'api_key', 'apikey', 'secret', 'client_secret',
        'private_key', 'session', 'remember_token', 'credentials',
    ];

    public static function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            $keyName = strtolower((string) $key);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $needle) {
                if (str_contains($keyName, $needle)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = self::redactArray($value);
            } elseif (is_string($value)) {
                $data[$key] = self::redactString($value);
            }
        }

        return $data;
    }

    public static function redactString(string $text): string
    {
        $patterns = [
            '/(Authorization\s*:\s*Bearer\s+)[^\s\r\n]+/i' => '$1[REDACTED]',
            '/(Cookie\s*:\s*)[^\r\n]+/i' => '$1[REDACTED]',
            '/(Set-Cookie\s*:\s*)[^\r\n]+/i' => '$1[REDACTED]',
            '/((?:password|passwd|secret|token|api[_-]?key)\s*[=:]\s*)[^\s&\r\n]+/i' => '$1[REDACTED]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }
}
