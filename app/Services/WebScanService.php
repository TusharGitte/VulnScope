<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Target;
use App\Models\VulnerabilityFinding;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Str;
use Throwable;

class WebScanService
{
    private int $maxPages = 100;

    public function __construct(
        private ScopeEnforcementService $scope,
        private HttpRequestService $http,
    ) {
        $this->maxPages = max(1, (int) config('vapt.max_crawl_pages', 100));
    }

    public function run(ScanRun $run): void
    {
        $project = $run->project()->firstOrFail();
        $target = $run->target()->firstOrFail();
        $run->update(['status' => 'running', 'progress_percent' => 1, 'started_at' => $run->started_at ?? now()]);

        $queue = [$target->normalized_url];
        $visited = [];
        $forms = [];
        $parameters = [];
        $probedHosts = [];

        try {
            while ($queue && count($visited) < $this->maxPages) {
                $run->refresh();
                if (in_array($run->status, ['cancelled', 'interrupted'], true)) return;

                $url = array_shift($queue);
                if (isset($visited[$url])) continue;
                $visited[$url] = true;

                try {
                    $parts = parse_url($url) ?: [];
                    $host = strtolower((string) ($parts['host'] ?? ''));
                    $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
                    $this->scope->assertInScope($project, $host, $port, url: $url);
                    $this->scope->assertResolvedIpsInScope($project, $host);
                } catch (\App\Exceptions\ScopeViolationException $e) {
                    $run->config = array_merge($run->config ?? [], [
                        'out_of_scope_discovered' => array_values(array_slice(array_unique(array_merge($run->config['out_of_scope_discovered'] ?? [], [$url])), -100)),
                    ]);
                    $run->save();
                    continue;
                }

                $response = $this->http->get($project, $url);
                $status = $response->getStatusCode();
                $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);
                $body = (string) $response->getBody();
                $bodySample = substr($body, 0, 1024 * 1024);

                $this->checkHeaders($project, $run, $target, $url, $headers);
                $this->checkCookies($project, $run, $target, $url, $headers['set-cookie'] ?? []);
                $this->checkSafeBodyIndicators($project, $run, $target, $url, $status, $bodySample);

                if (! isset($probedHosts[$host])) {
                    $probedHosts[$host] = true;
                    $this->checkSensitiveFiles($project, $run, $target, $url);
                    $this->checkCorsCredentialReflection($project, $run, $target, $url);
                    $this->checkTraceMethod($project, $run, $target, $url);
                }

                if ($this->isHtml($headers, $bodySample) && strlen($bodySample) <= 1024 * 1024) {
                    $this->extractLinksAndForms($url, $bodySample, $queue, $forms, $parameters);
                }

                $run->config = array_merge($run->config ?? [], [
                    'discovered_urls' => array_values(array_slice(array_keys($visited), 0, 500)),
                    'forms' => array_slice($forms, 0, 200),
                    'parameters' => array_values(array_unique(array_slice($parameters, 0, 500))),
                ]);
                $run->progress_percent = min(99, (int) round(count($visited) / $this->maxPages * 100));
                $run->save();
            }

            $run->update(['status' => 'completed', 'progress_percent' => 100, 'finished_at' => now()]);
            if ($project->current_step < Project::STEP_SCAN) {
                $project->update(['current_step' => Project::STEP_SCAN]);
            }
            \App\Models\AuditLog::record('scan.completed', 'success', $run->started_by, $project->id, $target->hostname, [
                'scan_run_id' => $run->id,
                'pages_visited' => count($visited),
                'forms_found' => count($forms),
            ]);
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'error_message' => SecretRedactor::redactString($e->getMessage()), 'finished_at' => now()]);
            \App\Models\AuditLog::record('scan.failed', 'failure', $run->started_by, $project->id, $target->hostname, ['scan_run_id' => $run->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function checkHeaders(Project $project, ScanRun $run, Target $target, string $url, array $headers): void
    {
        $hasCspFrameAncestors = isset($headers['content-security-policy']) && stripos(implode(';', $headers['content-security-policy']), 'frame-ancestors') !== false;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme === 'https' && ! isset($headers['strict-transport-security'])) {
            $this->finding($project, $run, $target, 'Missing HTTP Strict Transport Security (HSTS)', 'medium', 'high', 'headers', $url,
                'The HTTPS endpoint did not return a Strict-Transport-Security header.',
                'An active network attacker may be able to downgrade a first connection.',
                'Deploy HSTS with an appropriate max-age after verifying all in-scope subdomains.',
                'Header observation only.');
        }
        if (! isset($headers['content-security-policy'])) {
            $this->finding($project, $run, $target, 'Missing Content Security Policy (CSP)', 'medium', 'high', 'headers', $url,
                'No Content-Security-Policy header was observed on the endpoint.',
                'Missing CSP removes an important browser-side mitigation for content injection.',
                'Define a restrictive CSP appropriate to the application.', 'Header observation only.');
        }
        if (! isset($headers['x-frame-options']) && ! $hasCspFrameAncestors) {
            $this->finding($project, $run, $target, 'Missing Clickjacking Defense', 'low', 'high', 'headers', $url,
                'Neither X-Frame-Options nor CSP frame-ancestors was observed.',
                'The page may be frameable by another origin.',
                'Set X-Frame-Options or CSP frame-ancestors as appropriate.', 'Header observation only.');
        }
        if (! isset($headers['x-content-type-options'])) {
            $this->finding($project, $run, $target, 'Missing X-Content-Type-Options', 'low', 'high', 'headers', $url,
                'The nosniff response header was not observed.',
                'Some clients may MIME-sniff content in undesirable ways.',
                'Set X-Content-Type-Options: nosniff.', 'Header observation only.');
        }
        if (isset($headers['access-control-allow-origin']) && in_array('*', $headers['access-control-allow-origin'], true)) {
            $this->finding($project, $run, $target, 'Wildcard CORS Policy Observed', 'low', 'medium', 'cors', $url,
                'Access-Control-Allow-Origin: * was observed.',
                'Wildcard CORS can expose cross-origin readable resources when the resource is intended to be restricted.',
                'Restrict allowed origins to the minimum necessary set and review credentialed endpoints.', 'Header observation only.');
        }
        if (isset($headers['server']) || isset($headers['x-powered-by'])) {
            $exposed = trim(implode(' ', array_merge($headers['server'] ?? [], $headers['x-powered-by'] ?? [])));
            $this->finding($project, $run, $target, 'Server Technology Information Disclosure', 'informational', 'high', 'info_disclosure', $url,
                'The response exposes server/runtime banner information.',
                'Banner details can help fingerprint the technology stack.',
                'Reduce unnecessary server and runtime version disclosure.', 'Observed headers: ' . SecretRedactor::redactString($exposed));
        }
        if (! isset($headers['referrer-policy'])) {
            $this->finding($project, $run, $target, 'Missing Referrer-Policy Header', 'informational', 'high', 'headers', $url,
                'No Referrer-Policy header was observed on the endpoint.',
                'Without an explicit policy, full URLs (potentially including sensitive query parameters or tokens) may leak to third-party sites via the Referer header.',
                'Set a Referrer-Policy such as strict-origin-when-cross-origin or no-referrer.', 'Header observation only.');
        }
        if (! isset($headers['permissions-policy'])) {
            $this->finding($project, $run, $target, 'Missing Permissions-Policy Header', 'informational', 'medium', 'headers', $url,
                'No Permissions-Policy (formerly Feature-Policy) header was observed.',
                'Without it, the page does not explicitly restrict powerful browser features (camera, microphone, geolocation, etc.) for itself or embedded content.',
                'Define a Permissions-Policy that disables features the application does not use.', 'Header observation only.');
        }
        if (isset($headers['allow'])) {
            // Observed safe method metadata; no active method probing.
        }
    }

    private function checkCookies(Project $project, ScanRun $run, Target $target, string $url, array $cookies): void
    {
        foreach ($cookies as $cookie) {
            $name = trim(Str::before($cookie, '='));
            $lower = strtolower($cookie);
            if (! str_contains($lower, 'secure') && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https') {
                $this->finding($project, $run, $target, "Cookie '{$name}' Missing Secure Attribute", 'low', 'high', 'cookies', $url,
                    'A cookie set by an HTTPS endpoint was observed without the Secure attribute.',
                    'The cookie may be transmitted over cleartext HTTP if the client is induced to make such a request.',
                    'Set Secure on sensitive cookies.', 'Set-Cookie attribute observation.');
            }
            if (! str_contains($lower, 'httponly')) {
                $this->finding($project, $run, $target, "Cookie '{$name}' Missing HttpOnly Attribute", 'low', 'high', 'cookies', $url,
                    'A cookie was observed without HttpOnly.',
                    'Client-side scripts can potentially read the cookie value.',
                    'Set HttpOnly on cookies that should never be exposed to JavaScript.', 'Set-Cookie attribute observation.');
            }
            if (! str_contains($lower, 'samesite=')) {
                $this->finding($project, $run, $target, "Cookie '{$name}' Missing SameSite Attribute", 'low', 'high', 'cookies', $url,
                    'A cookie was observed without a SameSite attribute.',
                    'Cross-site request behavior is less constrained than it could be.',
                    'Set an appropriate SameSite value, especially for authentication/session cookies.', 'Set-Cookie attribute observation.');
            }
        }
    }

    private function checkSafeBodyIndicators(Project $project, ScanRun $run, Target $target, string $url, int $status, string $body): void
    {
        if ($status >= 500 && preg_match('/whoops|stack trace|exception class|traceback|debug mode/i', $body)) {
            $this->finding($project, $run, $target, 'Potential Debug/Error Detail Exposure', 'high', 'medium', 'error_disclosure', $url,
                'A server error page appears to contain framework debug or stack-trace indicators.',
                'Detailed error output may disclose implementation details, paths, queries, or secrets.',
                'Disable production debug output and return generic error pages.', 'Passive error-page fingerprint; analyst validation required.');
        }
        if (preg_match('/<title>\s*Index of\s*\//i', $body)) {
            $this->finding($project, $run, $target, 'Directory Listing Observed', 'medium', 'high', 'misconfiguration', $url,
                'The page title indicates a directory index.',
                'Directory listings can expose files and application structure.',
                'Disable directory indexing unless it is explicitly required.', 'Passive HTML fingerprint; analyst validation required.');
        }
        if (preg_match('/(?:SQL syntax.*MySQL|SQLSTATE\[[0-9A-Z]+\]|PostgreSQL.*ERROR|ORA-[0-9]{4,}|SQLite3?::query|Unclosed quotation mark)/i', $body)) {
            $this->finding($project, $run, $target, 'Potential Database Error Disclosure', 'high', 'medium', 'sqli-indicator', $url,
                'The response body contains recognizable database error text without an exploit payload being sent.',
                'Verbose database errors can disclose implementation details and may indicate an injection path that requires manual validation.',
                'Return generic application errors and validate all database inputs using parameterized queries.', 'Passive database error fingerprint; analyst validation required.');
        }
        if (preg_match('~(?:/bin/sh:|command not found|Traceback \(most recent call last\)|System\.Diagnostics\.)~i', $body)) {
            $this->finding($project, $run, $target, 'Potential Command/Error Detail Disclosure', 'medium', 'medium', 'command-injection-indicator', $url,
                'The response contains text commonly associated with command execution errors or runtime traces.',
                'Detailed command/runtime errors may expose execution context and increase attack surface.',
                'Suppress detailed command/runtime errors and validate server-side input.', 'Passive error fingerprint; analyst validation required.');
        }
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $queryParams);
            foreach ($queryParams as $name => $value) {
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $candidate) {
                    $candidate = trim((string) $candidate);
                    if ($candidate !== '' && strlen($candidate) >= 3 && str_contains($body, $candidate)) {
                        $this->finding($project, $run, $target, "Potential Reflected Input: {$name}", 'medium', 'medium', 'xss-indicator', $url,
                            'A value already present in the URL query string was observed in the response body.',
                            'Reflection can be a prerequisite for client-side injection depending on output context and encoding.',
                            'Review output encoding and apply context-appropriate escaping. Validate manually with a harmless test marker.', 'Passive query-value reflection; analyst validation required.');
                        break;
                    }
                }
                if (preg_match('/^(redirect|redirect_to|redirect_uri|return|return_to|returnurl|next|url|dest|destination|continue|target)$/i', (string) $name)) {
                    $candidateUrl = is_array($value) ? (string) reset($value) : (string) $value;
                    $candidateHost = strtolower((string) parse_url($candidateUrl, PHP_URL_HOST));
                    $pageHost = strtolower((string) parse_url($url, PHP_URL_HOST));
                    if ($candidateHost !== '' && $candidateHost !== $pageHost) {
                        $this->finding($project, $run, $target, "Potential Open Redirect Parameter: {$name}", 'medium', 'medium', 'open-redirect-indicator', $url,
                            "A parameter named '{$name}' holds a full URL pointing to a different host ({$candidateHost}).",
                            'If the application redirects to this URL without validating the destination, it could be abused for phishing or credential-theft redirects.',
                            'Validate redirect destinations against an allow-list of known internal paths/hosts, or use indirect (indexed) redirect targets.', 'Passive parameter-name/value heuristic; analyst validation required — no redirect was actually followed.');
                    }
                }
            }
        }

        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https') {
            if (preg_match_all('/\b(?:src|href)\s*=\s*["\']http:\/\/[^"\']+["\']/i', $body, $mixed) && count($mixed[0]) > 0) {
                $this->finding($project, $run, $target, 'Mixed Content: Insecure Sub-Resources on HTTPS Page', 'medium', 'high', 'mixed-content', $url,
                    'The HTTPS page references one or more sub-resources (scripts, stylesheets, images, etc.) over plain http://.',
                    'Insecure sub-resources can be tampered with by a network attacker even though the page itself loads over HTTPS, and browsers may block active mixed content outright.',
                    'Serve all sub-resources over HTTPS, or use protocol-relative/absolute HTTPS URLs.', 'Passive HTML fingerprint; ' . count($mixed[0]) . ' insecure reference(s) observed.');
            }
        }

        if (preg_match_all('/<script[^>]+src=["\']((?:https?:)?\/\/[^"\']+)["\'][^>]*>/i', $body, $scripts, PREG_SET_ORDER)) {
            $pageHost = strtolower((string) parse_url($url, PHP_URL_HOST));
            $missingSri = 0;
            foreach ($scripts as $scriptTag) {
                $src = $scriptTag[1];
                $scriptHost = strtolower((string) parse_url($src, PHP_URL_HOST));
                if ($scriptHost !== '' && $scriptHost !== $pageHost && stripos($scriptTag[0], 'integrity=') === false) {
                    $missingSri++;
                }
            }
            if ($missingSri > 0) {
                $this->finding($project, $run, $target, 'Third-Party Script Loaded Without Subresource Integrity (SRI)', 'low', 'medium', 'sri', $url,
                    "The page loads {$missingSri} cross-origin <script> tag(s) without an integrity attribute.",
                    'If the third-party host or CDN is compromised, the served script can execute arbitrary code in the context of this page with no built-in tamper detection.',
                    'Add integrity (SRI hash) and crossorigin attributes to externally-hosted <script>/<link> tags where the resource is static and versioned.', 'Passive HTML fingerprint; analyst validation required.');
            }
        }

        if (preg_match('/<input[^>]+type=["\']password["\'][^>]*>/i', $body, $pwField)) {
            if (! preg_match('/autocomplete\s*=\s*["\']off["\']/i', $pwField[0])) {
                $this->finding($project, $run, $target, 'Password Field Without autocomplete="off"/"new-password"', 'informational', 'medium', 'misconfiguration', $url,
                    'A password input field was observed without an explicit autocomplete restriction.',
                    'Browsers and password managers may cache or auto-fill the credential on shared or public machines.',
                    'Set autocomplete="new-password" (for registration/change forms) or a deliberate policy appropriate to the form.', 'Passive HTML fingerprint; analyst validation required.');
            }
        }
    }

    /**
     * Once per host: probe a small, fixed list of well-known sensitive paths with a single
     * safe GET each. This is a standard passive VAPT technique (no fuzzing/brute force) —
     * it only checks whether commonly-forgotten files are unintentionally publicly exposed.
     */
    private function checkSensitiveFiles(Project $project, ScanRun $run, Target $target, string $anyUrlOnHost): void
    {
        $base = parse_url($anyUrlOnHost);
        if (! $base || empty($base['host'])) return;
        $root = ($base['scheme'] ?? 'http') . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');

        $probes = [
            '/.env' => 'Exposed .env File',
            '/.git/HEAD' => 'Exposed .git Directory',
            '/.git/config' => 'Exposed .git Directory',
            '/wp-config.php.bak' => 'Exposed Backup Configuration File',
            '/config.php.bak' => 'Exposed Backup Configuration File',
            '/.DS_Store' => 'Exposed .DS_Store File',
            '/backup.zip' => 'Exposed Backup Archive',
            '/.svn/entries' => 'Exposed .svn Directory',
        ];

        foreach ($probes as $path => $title) {
            $url = $root . $path;
            try {
                $parts = parse_url($url) ?: [];
                $host = strtolower((string) ($parts['host'] ?? ''));
                $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
                $this->scope->assertInScope($project, $host, $port, url: $url);
                $this->scope->assertResolvedIpsInScope($project, $host);
            } catch (\App\Exceptions\ScopeViolationException) {
                continue;
            }

            try {
                $response = $this->http->get($project, $url);
            } catch (Throwable) {
                continue;
            }

            if ($response->getStatusCode() !== 200) continue;
            $sample = substr((string) $response->getBody(), 0, 2048);
            // Skip generic soft-404 pages that return 200 with an HTML "not found" body.
            if (preg_match('/<html\b|<!doctype html/i', $sample) && preg_match('/not found|404|error/i', $sample)) continue;
            if (trim($sample) === '') continue;

            $this->finding($project, $run, $target, $title, 'high', 'medium', 'sensitive-file-exposure', $url,
                "A GET request to {$path} returned HTTP 200 with non-empty, non-error-page content.",
                'Configuration files, VCS metadata, or backup archives can disclose credentials, source code, or infrastructure details.',
                'Remove the file from the public web root or block access to it at the web-server/reverse-proxy level.',
                'Single safe GET request; response status and a short content sample were recorded. No file contents beyond the sample were retained.');
        }
    }

    /**
     * Once per host: sends one GET with a bogus, attacker-controlled Origin header (no state
     * change) and checks whether the server reflects that exact origin back while also allowing
     * credentials — a much stronger CORS misconfiguration than a plain wildcard.
     */
    private function checkCorsCredentialReflection(Project $project, ScanRun $run, Target $target, string $url): void
    {
        $probeOrigin = 'https://vapt-cors-probe.invalid';
        try {
            $response = $this->http->get($project, $url, ['headers' => ['Origin' => $probeOrigin]]);
        } catch (Throwable) {
            return;
        }
        $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);
        $allowOrigin = strtolower(trim(implode('', $headers['access-control-allow-origin'] ?? [])));
        $allowCreds = strtolower(trim(implode('', $headers['access-control-allow-credentials'] ?? [])));

        if ($allowOrigin === strtolower($probeOrigin) && $allowCreds === 'true') {
            $this->finding($project, $run, $target, 'CORS: Arbitrary Origin Reflected With Credentials Allowed', 'high', 'high', 'cors', $url,
                'The server reflected an arbitrary, attacker-supplied Origin header back in Access-Control-Allow-Origin while also sending Access-Control-Allow-Credentials: true.',
                'Any website can make credentialed cross-origin requests (with the victim\'s cookies/session) to this endpoint and read the response, effectively bypassing the Same-Origin Policy for authenticated users.',
                'Never reflect an arbitrary Origin when Allow-Credentials is true. Validate Origin against an explicit allow-list.',
                "Probe Origin sent: {$probeOrigin}; observed Access-Control-Allow-Origin: {$allowOrigin}, Access-Control-Allow-Credentials: {$allowCreds}.");
        }
    }

    /**
     * Once per host: sends a single safe OPTIONS request and inspects the Allow header for
     * the classic Cross-Site Tracing (XST) method, TRACE, and for broad write-method exposure.
     */
    private function checkTraceMethod(Project $project, ScanRun $run, Target $target, string $url): void
    {
        try {
            $optionsResponse = $this->httpOptions($project, $url);
        } catch (Throwable) {
            return;
        }
        $headers = array_change_key_case($optionsResponse->getHeaders(), CASE_LOWER);
        $allow = strtoupper(implode(',', $headers['allow'] ?? []));
        if ($allow === '') return;

        if (str_contains($allow, 'TRACE')) {
            $this->finding($project, $run, $target, 'HTTP TRACE Method Enabled', 'medium', 'high', 'misconfiguration', $url,
                "The Allow header on an OPTIONS response lists TRACE: {$allow}.",
                'TRACE can be combined with cross-site scripting (Cross-Site Tracing) to read cookies and headers otherwise inaccessible to JavaScript, such as HttpOnly cookies, on older/misconfigured browsers.',
                'Disable the TRACE method at the web server or load balancer.', 'Observed via a single OPTIONS request; Allow header recorded.');
        }
        if (str_contains($allow, 'PUT') || str_contains($allow, 'DELETE')) {
            $this->finding($project, $run, $target, 'State-Changing HTTP Methods Advertised', 'informational', 'medium', 'misconfiguration', $url,
                "The Allow header advertises: {$allow}.",
                'If PUT/DELETE are actually accepted without proper authentication/authorization on this path, they could allow unauthorized content modification or deletion.',
                'Confirm these methods are authenticated and authorized server-side, or disable them where unused.', 'Observed via a single OPTIONS request; no PUT/DELETE request was sent.');
        }
    }

    private function httpOptions(Project $project, string $url)
    {
        $parts = parse_url($url) ?: [];
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $this->scope->assertInScope($project, $host, $port, url: $url);
        $this->scope->assertResolvedIpsInScope($project, $host);

        return $this->http->client()->request('OPTIONS', $url, ['timeout' => 8]);
    }

    private function extractLinksAndForms(string $baseUrl, string $body, array &$queue, array &$forms, array &$parameters): void
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $body, $links);
        foreach (array_slice($links[1] ?? [], 0, 200) as $href) {
            $url = $this->resolveUrl($baseUrl, html_entity_decode($href));
            if ($url !== null && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $queue[] = $url;
            }
        }

        preg_match_all('/<form([^>]*)>(.*?)<\/form>/is', $body, $matches, PREG_SET_ORDER);
        foreach (array_slice($matches, 0, 100) as $match) {
            $attrs = $match[1] ?? '';
            $action = '';
            if (preg_match('/action=["\']([^"\']*)["\']/i', $attrs, $a)) $action = $a[1];
            $method = 'GET';
            if (preg_match('/method=["\']([^"\']+)["\']/i', $attrs, $m)) $method = strtoupper($m[1]);
            $formUrl = $this->resolveUrl($baseUrl, $action ?: $baseUrl);
            $formData = ['action' => $formUrl, 'method' => $method, 'parameters' => []];
            preg_match_all('/<(?:input|textarea|select)[^>]+name=["\']([^"\']+)["\']/i', $match[2] ?? '', $names);
            foreach (array_unique($names[1] ?? []) as $name) {
                $parameters[] = $name;
                $formData['parameters'][] = $name;
            }
            $forms[] = $formData;
        }

        $queue = array_values(array_unique($queue));
    }

    private function resolveUrl(string $base, string $location): ?string
    {
        $location = trim(html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($location === '' || str_starts_with($location, '#') || preg_match('#^(?:mailto|javascript|tel|data):#i', $location)) {
            return null;
        }
        try {
            return $this->stripFragment((string) UriResolver::resolve(new Uri($base), new Uri($location)));
        } catch (\Throwable) {
            return null;
        }
    }

    private function stripFragment(string $url): string
    {
        return Str::before($url, '#');
    }

    private function isHtml(array $headers, string $body): bool
    {
        $type = strtolower(implode(';', $headers['content-type'] ?? []));
        return str_contains($type, 'text/html') || preg_match('/<html\b/i', $body) === 1;
    }

    private function finding(Project $project, ScanRun $run, Target $target, string $title, string $severity, string $confidence, string $category, string $url, string $description, string $impact, string $remediation, string $evidence): VulnerabilityFinding
    {
        $existing = VulnerabilityFinding::where('scan_run_id', $run->id)->where('title', $title)->where('url', $url)->first();
        if ($existing) return $existing;

        $finding = VulnerabilityFinding::create([
            'project_id' => $project->id,
            'scan_run_id' => $run->id,
            'target_id' => $target->id,
            'title' => $title,
            'severity' => $severity,
            'confidence' => $confidence,
            'category' => $category,
            'url' => $url,
            'description' => $description,
            'impact' => $impact,
            'remediation' => $remediation,
            'reproduction_guidance' => 'Review the recorded passive evidence and validate manually within the approved scope. The automated scanner does not execute destructive exploit payloads.',
            'status' => 'needs_review',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        Evidence::create([
            'finding_id' => $finding->id,
            'type' => 'note',
            'content' => SecretRedactor::redactString($evidence),
            'secrets_redacted' => true,
            'captured_by' => $run->started_by,
        ]);

        return $finding;
    }
}
