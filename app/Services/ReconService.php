<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ReconResult;
use App\Models\ScanRun;
use App\Models\Target;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Str;
use Throwable;

class ReconService
{
    public function __construct(
        private ScopeEnforcementService $scope,
        private HttpRequestService $http,
    ) {
    }

    public function run(ScanRun $run): void
    {
        $project = $run->project()->firstOrFail();
        $target = $run->target()->firstOrFail();
        $run->update(['status' => 'running', 'progress_percent' => 1, 'started_at' => $run->started_at ?? now()]);

        try {
            $this->scope->assertInScope($project, $target->hostname, $this->port($target), url: $target->normalized_url);
            $this->scope->assertResolvedIpsInScope($project, $target->hostname);

            $this->save($run, $target, 'overview', 'input_url', $target->input_url, 'certain', 'Project target configuration');
            $this->save($run, $target, 'overview', 'normalized_url', $target->normalized_url, 'certain', 'URL normalization');
            $this->save($run, $target, 'overview', 'hostname', $target->hostname, 'certain', 'URL parser');

            $ips = $this->scope->resolveIps($target->hostname);
            foreach ($ips as $ip) {
                $isPublic = (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
                $this->save($run, $target, 'network', filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4', $ip, 'certain', 'DNS A/AAAA lookup');
                $this->save($run, $target, 'network', $ip . ' visibility', $isPublic ? 'public' : 'private/reserved', 'certain', 'Local IP classification');
            }
            $run->update(['progress_percent' => 20]);

            foreach ([DNS_A, DNS_AAAA, DNS_CNAME, DNS_MX, DNS_NS, DNS_TXT] as $type) {
                $records = @dns_get_record($target->hostname, $type) ?: [];
                foreach ($records as $record) {
                    $kind = $record['type'] ?? 'UNKNOWN';
                    $value = match ($kind) {
                        'A' => $record['ip'] ?? '',
                        'AAAA' => $record['ipv6'] ?? '',
                        'CNAME', 'NS' => $record['target'] ?? '',
                        'MX' => ($record['target'] ?? '') . ' (priority ' . ($record['pri'] ?? '') . ')',
                        'TXT' => $record['txt'] ?? '',
                        default => json_encode($record, JSON_UNESCAPED_SLASHES) ?: '',
                    };
                    if ($value !== '') {
                        $this->save($run, $target, 'dns', $kind, $value, 'certain', 'DNS lookup');
                    }
                }
            }
            $run->update(['progress_percent' => 35]);

            $response = $this->probeHttp($project, $target->normalized_url);
            $this->save($run, $target, 'http', 'status_code', (string) $response['response']->getStatusCode(), 'certain', 'HTTP probe');
            $this->save($run, $target, 'http', 'content_type', $response['response']->getHeaderLine('Content-Type') ?: 'unknown', 'high', 'HTTP response header');
            $this->save($run, $target, 'http', 'content_encoding', $response['response']->getHeaderLine('Content-Encoding') ?: 'none', 'high', 'HTTP response header');
            $this->save($run, $target, 'http', 'redirects', (string) count($response['redirects']), 'high', 'HTTP redirect chain');

            foreach ($response['redirects'] as $index => $redirect) {
                $this->save($run, $target, 'http', 'redirect_' . ($index + 1), $redirect, 'certain', 'Location header');
            }

            foreach ($response['response']->getHeaders() as $name => $values) {
                $value = SecretRedactor::redactString(implode(', ', $values));
                $this->save($run, $target, 'headers', $name, $value, 'certain', 'HTTP response header');
            }

            foreach ($response['response']->getHeader('Set-Cookie') as $cookie) {
                $name = trim((string) Str::before($cookie, '='));
                $attributes = strtolower($cookie);
                $this->save($run, $target, 'http', 'cookie_' . ($name ?: 'unknown') . '_secure', str_contains($attributes, 'secure') ? 'yes' : 'no', 'certain', 'Set-Cookie');
                $this->save($run, $target, 'http', 'cookie_' . ($name ?: 'unknown') . '_httponly', str_contains($attributes, 'httponly') ? 'yes' : 'no', 'certain', 'Set-Cookie');
                $this->save($run, $target, 'http', 'cookie_' . ($name ?: 'unknown') . '_samesite', preg_match('/samesite=([^;]+)/i', $cookie, $m) ? $m[1] : 'missing', 'high', 'Set-Cookie');
            }
            $run->update(['progress_percent' => 55]);

            $body = (string) $response['body'];
            $this->recordTechnology($run, $target, $response['response'], $body);
            $this->collectRobotsAndSitemap($project, $run, $target);
            $this->collectTls($run, $target);
            $run->update(['progress_percent' => 85]);

            $this->save($run, $target, 'historical', 'first_seen', (string) $target->created_at, 'certain', 'VAPT Platform target record');
            $this->save($run, $target, 'historical', 'external_registration_history', 'Not collected by default; enable an external RDAP provider explicitly if required.', 'low', 'Platform configuration');

            $run->update(['status' => 'completed', 'progress_percent' => 100, 'finished_at' => now()]);
            $target->update(['status' => 'active']);
            if ($project->current_step < Project::STEP_RECON) {
                $project->update(['current_step' => Project::STEP_RECON]);
            }

            AuditLog::record('recon.completed', 'success', $run->started_by, $project->id, $target->hostname, ['scan_run_id' => $run->id]);
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'progress_percent' => min(99, (int) $run->progress_percent),
                'error_message' => SecretRedactor::redactString($e->getMessage()),
                'finished_at' => now(),
            ]);
            AuditLog::record('recon.failed', 'failure', $run->started_by, $project->id, $target->hostname, ['scan_run_id' => $run->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function probeHttp(Project $project, string $url): array
    {
        $redirects = [];
        $current = $url;
        $response = null;

        for ($i = 0; $i <= 5; $i++) {
            $response = $this->http->get($project, $current);
            $location = $response->getHeaderLine('Location');
            if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400 && $location !== '') {
                $next = $this->resolveUrl($current, $location);
                $nextParts = parse_url($next);
                $nextHost = $nextParts['host'] ?? '';
                $nextPort = (int) ($nextParts['port'] ?? (($nextParts['scheme'] ?? 'https') === 'https' ? 443 : 80));
                $this->scope->assertInScope($project, $nextHost, $nextPort, url: $next);
                $redirects[] = $next;
                $current = $next;
                continue;
            }
            break;
        }

        if ($response === null) {
            throw new \RuntimeException('No HTTP response received.');
        }

        $body = $response->getBody();
        $body = method_exists($body, 'read') ? $body->read(1024 * 1024) : (string) $body;
        return ['response' => $response, 'body' => $body, 'redirects' => $redirects];
    }

    private function collectRobotsAndSitemap(Project $project, ScanRun $run, Target $target): void
    {
        $parts = parse_url($target->normalized_url);
        $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (! empty($parts['port'])) $base .= ':' . $parts['port'];
        $base .= '/';
        foreach (['robots.txt' => 'robots', 'sitemap.xml' => 'endpoints'] as $file => $section) {
            $url = $base . $file;
            try {
                $response = $this->http->get($project, $url);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                    $body = (string) $response->getBody();
                    $body = substr($body, 0, 50000);
                    if ($section === 'robots') {
                        $this->save($run, $target, 'endpoints', 'robots_txt', $body, 'certain', 'robots.txt');
                    } else {
                        $urls = [];
                        preg_match_all('/<loc>\s*(.*?)\s*<\/loc>/is', $body, $m);
                        foreach (array_slice($m[1] ?? [], 0, 100) as $u) $urls[] = trim(strip_tags($u));
                        $this->save($run, $target, 'endpoints', 'sitemap_urls', implode("\n", $urls), 'high', 'sitemap.xml');
                    }
                }
            } catch (Throwable $e) {
                // Optional ancillary endpoint; do not fail the whole recon run.
            }
        }
    }

    private function recordTechnology(ScanRun $run, Target $target, $response, string $body): void
    {
        $server = $response->getHeaderLine('Server');
        $powered = $response->getHeaderLine('X-Powered-By');
        if ($server !== '') $this->save($run, $target, 'tech_stack', 'web_server', $server, 'high', 'Server header');
        if ($powered !== '') $this->save($run, $target, 'tech_stack', 'runtime_framework', $powered, 'high', 'X-Powered-By header');

        $patterns = [
            'WordPress' => ['/wp-content\//i', '/wp-includes\//i'],
            'Laravel' => ['/_token/i', '/laravel_session/i', '/XSRF-TOKEN/i'],
            'Next.js' => ['/_next\//i'],
            'React' => ['/data-reactroot/i', '/react/i'],
            'Vue.js' => ['/vue(?:\.min)?\.js/i'],
            'Angular' => ['/ng-version/i'],
            'Bootstrap' => ['/bootstrap(?:\.min)?\.css/i'],
        ];
        foreach ($patterns as $tech => $needles) {
            foreach ($needles as $needle) {
                if (preg_match($needle, $body)) {
                    $this->save($run, $target, 'tech_stack', 'technology', $tech, 'medium', 'HTTP body fingerprint');
                    break;
                }
            }
        }

        if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)/i', $body, $m)) {
            $this->save($run, $target, 'tech_stack', 'generator', trim($m[1]), 'high', 'HTML meta generator');
        }
    }

    private function collectTls(ScanRun $run, Target $target): void
    {
        if (strtolower((string) parse_url($target->normalized_url, PHP_URL_SCHEME)) !== 'https' || ! function_exists('stream_socket_client')) return;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'SNI_enabled' => true,
                'peer_name' => $target->hostname,
            ],
        ]);
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client('ssl://' . $target->hostname . ':' . $this->port($target), $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context);
        if (! $socket) {
            $this->save($run, $target, 'tls', 'probe_error', $errstr ?: 'TLS connection failed', 'low', 'TLS socket probe');
            return;
        }

        $meta = stream_get_meta_data($socket);
        $crypto = $meta['crypto'] ?? [];
        if (! empty($crypto['protocol'])) $this->save($run, $target, 'tls', 'protocol', (string) $crypto['protocol'], 'high', 'TLS handshake');
        if (! empty($crypto['cipher_name'])) $this->save($run, $target, 'tls', 'cipher', (string) $crypto['cipher_name'], 'high', 'TLS handshake');
        if (! empty($crypto['cipher_bits'])) $this->save($run, $target, 'tls', 'cipher_bits', (string) $crypto['cipher_bits'], 'high', 'TLS handshake');

        $options = stream_context_get_options($socket);
        $cert = $options['ssl']['peer_certificate'] ?? null;
        if ($cert) {
            $parsed = @openssl_x509_parse($cert) ?: [];
            $subject = $parsed['subject']['CN'] ?? null;
            if ($subject) $this->save($run, $target, 'tls', 'certificate_subject', (string) $subject, 'certain', 'Peer certificate');
            if (! empty($parsed['issuer']['CN'])) $this->save($run, $target, 'tls', 'certificate_issuer', (string) $parsed['issuer']['CN'], 'certain', 'Peer certificate');
            if (! empty($parsed['validTo_time_t'])) $this->save($run, $target, 'tls', 'certificate_expiry', date(DATE_ATOM, $parsed['validTo_time_t']), 'certain', 'Peer certificate');
        }
        fclose($socket);
    }

    private function resolveUrl(string $base, string $location): ?string
    {
        $location = trim(html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($location === '' || str_starts_with($location, '#') || preg_match('#^(?:mailto|javascript|tel|data):#i', $location)) {
            return null;
        }
        try {
            return Str::before((string) UriResolver::resolve(new Uri($base), new Uri($location)), '#');
        } catch (\Throwable) {
            return null;
        }
    }

    private function save(ScanRun $run, Target $target, string $section, string $key, string $value, string $confidence, string $source): void
    {
        ReconResult::create([
            'scan_run_id' => $run->id,
            'target_id' => $target->id,
            'section' => $section,
            'key' => $key,
            'value' => SecretRedactor::redactString($value),
            'confidence' => $confidence,
            'source' => $source,
        ]);
    }

    private function port(Target $target): int
    {
        return (int) (parse_url($target->normalized_url, PHP_URL_PORT) ?: (parse_url($target->normalized_url, PHP_URL_SCHEME) === 'https' ? 443 : 80));
    }
}
