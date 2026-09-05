<?php

namespace Tests\Unit;

use App\Services\TargetUrlService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TargetUrlServiceTest extends TestCase
{
    public function test_normalizes_http_https_hosts_and_ports(): void
    {
        $https = TargetUrlService::normalize('HTTPS://Example.COM:8443/path?q=1#fragment');
        self::assertSame('https://example.com:8443/path?q=1', $https['normalized_url']);
        self::assertSame('example.com', $https['hostname']);
        self::assertSame(8443, $https['port']);
    }

    public function test_rejects_embedded_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TargetUrlService::normalize('https://user:pass@example.com/');
    }

    public function test_rejects_non_http_schemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TargetUrlService::normalize('file:///etc/passwd');
    }
}
