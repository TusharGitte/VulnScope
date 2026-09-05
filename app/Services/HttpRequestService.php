<?php

namespace App\Services;

use App\Models\Project;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Request as LaravelRequest;
use RuntimeException;

class HttpRequestService
{
    public function __construct(private ScopeEnforcementService $scope)
    {
    }

    public function client(): Client
    {
        $config = [
            'timeout' => 8,
            'connect_timeout' => 5,
            'verify' => true,
            'http_errors' => false,
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'VAPT-Platform/2.0 (Authorized Security Assessment)',
                'Accept' => 'text/html,application/xhtml+xml,application/json,text/plain;q=0.8,*/*;q=0.2',
            ],
        ];

        if ($proxy = env('VAPT_HTTP_PROXY')) {
            $config['proxy'] = $proxy;
        }

        return new Client($config);
    }

    public function requestAsync(Project $project, string $method, string $url, array $options = []): PromiseInterface
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $this->scope->assertInScope($project, $host, $port, null, $url);
        $this->scope->assertResolvedIpsInScope($project, $host);

        if ($this->isSelfUrl($host, $port)) {
            try {
                $headers = $options['headers'] ?? [];
                $server = [];
                foreach ($headers as $name => $value) {
                    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
                    if (strtolower($name) === 'content-type') $serverKey = 'CONTENT_TYPE';
                    if (strtolower($name) === 'content-length') $serverKey = 'CONTENT_LENGTH';
                    $server[$serverKey] = is_array($value) ? implode(', ', $value) : $value;
                }
                $request = LaravelRequest::create($url, $method, [], [], [], $server, $options['body'] ?? null);
                $response = app()->handle($request);
                return \GuzzleHttp\Promise\Create::promiseFor(new GuzzleResponse($response->getStatusCode(), $response->headers->all(), $response->getContent()));
            } catch (\Throwable $e) {
                return \GuzzleHttp\Promise\Create::rejectionFor($e);
            }
        }

        $options = $this->pinResolvedIp($project, $url, $options);
        return $this->client()->requestAsync($method, $url, $options);
    }

    public function get(Project $project, string $url, array $options = [])
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $port = isset($parts['port']) ? (int) $parts['port'] : (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
        $this->scope->assertInScope($project, $host, $port, null, $url);
        $this->scope->assertResolvedIpsInScope($project, $host);

        if ($this->isSelfUrl($host, $port)) {
            $request = LaravelRequest::create($url, 'GET');
            $response = app()->handle($request);
            return new GuzzleResponse($response->getStatusCode(), $response->headers->all(), $response->getContent());
        }

        $options = $this->pinResolvedIp($project, $url, $options);
        return $this->client()->get($url, $options);
    }

    /**
     * True only when the target's host AND port both match this application's own
     * configured APP_URL. This intentionally does NOT match on hostname alone —
     * matching any "127.0.0.1"/"localhost" target regardless of port would silently
     * redirect requests meant for a *different* locally-hosted service (e.g. a staging
     * app on 127.0.0.1:9001) back into this very application, producing findings that
     * describe the VAPT platform itself instead of the intended target.
     */
    private function isSelfUrl(string $host, int $port): bool
    {
        $self = parse_url((string) config('app.url'));
        $selfHost = strtolower((string) ($self['host'] ?? ''));
        $selfPort = (int) ($self['port'] ?? (($self['scheme'] ?? 'http') === 'https' ? 443 : 80));

        if ($selfHost === '' || $host === '') {
            return false;
        }

        $selfIsLoopback = in_array($selfHost, ['localhost', '127.0.0.1', '::1'], true);
        $hostIsLoopback = in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        // Either the hosts match exactly, or both resolve to "loopback" (so
        // 127.0.0.1 vs localhost still count as the same machine) — but the
        // port must always match, since that's what actually distinguishes
        // "this app" from "some other service on the same box".
        $sameHost = $host === $selfHost || ($selfIsLoopback && $hostIsLoopback);

        return $sameHost && $port === $selfPort;
    }

    private function pinResolvedIp(Project $project, string $url, array $options): array
    {
        if (! function_exists('curl_init') || ! defined('CURLOPT_RESOLVE')) {
            return $options;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return $options;
        }

        $port = (int) ($parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80));
        $ips = $this->scope->resolveIps($host);
        if ($ips === []) {
            return $options;
        }

        $this->scope->assertResolvedIpsInScope($project, $host);
        $options['curl'][CURLOPT_RESOLVE] = ["{$host}:{$port}:{$ips[0]}"];
        return $options;
    }
}
