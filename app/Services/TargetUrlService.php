<?php

namespace App\Services;

use InvalidArgumentException;

class TargetUrlService
{
    public static function normalize(string $input): array
    {
        $input = trim($input);
        $parts = parse_url($input);

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Target URL must use http:// or https://.');
        }

        if (empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Target URL must contain a hostname and no embedded credentials.');
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        $scheme = strtolower($parts['scheme']);
        $port = isset($parts['port']) ? (int) $parts['port'] : null;

        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException('Target URL contains an invalid port.');
        }

        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }

        $normalized = $scheme . '://' . self::formatHost($host, $parts['port'] ?? null) . $path;
        if (isset($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }

        return [
            'input_url' => $input,
            'normalized_url' => $normalized,
            'scheme' => $scheme,
            'hostname' => $host,
            'port' => $port ?? ($scheme === 'https' ? 443 : 80),
        ];
    }

    private static function formatHost(string $host, ?int $port): string
    {
        $displayHost = str_contains($host, ':') && ! str_starts_with($host, '[') ? '[' . $host . ']' : $host;
        if ($port !== null) {
            return $displayHost . ':' . $port;
        }
        return $displayHost;
    }
}
