<?php

namespace App\Services;

use App\Exceptions\ScopeViolationException;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ScopeRule;
use Illuminate\Support\Str;

class ScopeEnforcementService
{
    public function activeRuleFor(Project $project): ?ScopeRule
    {
        return $project->activeScopeRule();
    }

    public function assertInScope(Project $project, string $hostname, ?int $port = null, ?string $ip = null, ?string $url = null): ScopeRule
    {
        $hostname = strtolower(rtrim(trim($hostname), '.'));
        $rule = $this->activeRuleFor($project);

        if (! $rule) {
            $this->block($project, $hostname, 'no_active_authorization', 'No confirmed, currently-active scope/authorization window for this project.');
        }

        if (! $rule->isWithinWindow()) {
            $this->block($project, $hostname, 'outside_time_window', 'Current time is outside the authorized assessment window.');
        }

        if ($this->hostExcluded($rule, $hostname)) {
            $this->block($project, $hostname, 'host_excluded', "Host '{$hostname}' is explicitly excluded from scope.");
        }

        if (! $this->hostAllowed($rule, $hostname)) {
            $this->block($project, $hostname, 'host_not_allowed', "Host '{$hostname}' is not on the allowed_domains list.");
        }

        if ($port !== null && ! empty($rule->allowed_ports) && ! in_array((int) $port, array_map('intval', $rule->allowed_ports), true)) {
            $this->block($project, $hostname, 'port_not_allowed', "Port {$port} is not on the allowed_ports list.");
        }

        if ($url !== null && ! $this->urlAllowed($rule, $url)) {
            $this->block($project, $hostname, 'endpoint_not_allowed', "Endpoint '{$url}' is not on the allowed_endpoints list.");
        }

        if ($ip !== null && ! empty($rule->allowed_ip_ranges) && ! $this->ipAllowed($rule, $ip)) {
            $this->block($project, $hostname, 'ip_not_allowed', "Resolved IP '{$ip}' is outside the allowed CIDR ranges.");
        }

        return $rule;
    }

    public function assertResolvedIpsInScope(Project $project, string $hostname): ScopeRule
    {
        $rule = $this->assertInScope($project, $hostname);
        if (empty($rule->allowed_ip_ranges)) {
            return $rule;
        }

        $ips = $this->resolveIps($hostname);
        if ($ips === []) {
            $this->block($project, $hostname, 'dns_resolution_failed', 'The target hostname could not be resolved to an IP address.');
        }

        $hasExplicitCidrs = ! empty($rule->allowed_ip_ranges);
        foreach ($ips as $ip) {
            if ($hasExplicitCidrs) {
                if (! $this->ipAllowed($rule, $ip)) {
                    $this->block($project, $hostname, 'resolved_ip_not_allowed', "Resolved IP '{$ip}' is outside the allowed CIDR ranges.");
                }
                continue;
            }

            $isPublic = (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if (! $isPublic) {
                $this->block($project, $hostname, 'private_ip_requires_explicit_cidr', "Hostname resolves to non-public IP '{$ip}'. Add an explicit allowed IP/CIDR to authorize private/reserved infrastructure.");
            }
        }

        return $rule;
    }

    public function resolveIps(string $hostname): array
    {
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            return [$hostname];
        }

        $hostname = strtolower(rtrim($hostname, '.'));
        if ($hostname === 'localhost') {
            return ['127.0.0.1', '::1'];
        }

        $ips = [];
        foreach ([DNS_A, DNS_AAAA] as $type) {
            $records = @dns_get_record($hostname, $type) ?: [];
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function hostAllowed(ScopeRule $rule, string $hostname): bool
    {
        foreach ($rule->allowed_domains ?? [] as $pattern) {
            if ($this->matchesDomainPattern((string) $pattern, $hostname)) {
                return true;
            }
        }
        return false;
    }

    private function hostExcluded(ScopeRule $rule, string $hostname): bool
    {
        foreach ($rule->excluded_hosts ?? [] as $pattern) {
            if ($this->matchesDomainPattern((string) $pattern, $hostname)) {
                return true;
            }
        }
        return false;
    }

    private function matchesDomainPattern(string $pattern, string $hostname): bool
    {
        $pattern = strtolower(rtrim(trim($pattern), '.'));
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($pattern === '') {
            return false;
        }

        if (str_starts_with($pattern, '*.')) {
            $suffix = substr($pattern, 1);
            return str_ends_with($hostname, $suffix) && $hostname !== substr($pattern, 2);
        }

        return $hostname === $pattern;
    }

    private function urlAllowed(ScopeRule $rule, string $url): bool
    {
        $patterns = array_filter($rule->allowed_endpoints ?? []);
        if ($patterns === []) {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $pathWithQuery = $query !== null ? $path . '?' . $query : $path;

        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);
            if ($pattern === '') continue;

            if (str_starts_with($pattern, 'http://') || str_starts_with($pattern, 'https://')) {
                if (Str::is($pattern, $url)) return true;
            } elseif (Str::is($pattern, $pathWithQuery) || Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function ipAllowed(ScopeRule $rule, string $ip): bool
    {
        foreach ($rule->allowed_ip_ranges ?? [] as $cidr) {
            if ($this->ipInCidr($ip, (string) $cidr)) {
                return true;
            }
        }
        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', trim($cidr), 2), 2, null);
        if ($bits === null) {
            return $ip === $subnet;
        }

        $ipPacked = @inet_pton($ip);
        $subnetPacked = @inet_pton($subnet);
        $bits = (int) $bits;

        if ($ipPacked === false || $subnetPacked === false || strlen($ipPacked) !== strlen($subnetPacked)) {
            return false;
        }

        $maxBits = strlen($ipPacked) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipPacked, 0, $fullBytes) !== substr($subnetPacked, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) return true;

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipPacked[$fullBytes]) & $mask) === (ord($subnetPacked[$fullBytes]) & $mask);
    }

    private function block(Project $project, string $hostname, string $reason, string $message): never
    {
        AuditLog::record('scope.blocked', 'blocked', auth()->id(), $project->id, $hostname, [
            'reason' => $reason,
            'message' => $message,
        ]);

        throw new ScopeViolationException($message);
    }
}
