<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    public function test_only_known_proxy_can_forward_client_ip_and_https(): void
    {
        config(['trustedproxy.proxies' => ['10.20.0.5']]);
        try {
            foreach (['10.20.0.5' => true, '198.51.100.9' => false] as $remote => $trusted) {
                $request = Request::create('http://horizonte.test/nosotros', server: [
                    'REMOTE_ADDR' => $remote,
                    'HTTP_X_FORWARDED_FOR' => '203.0.113.20',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                ]);
                (new TrustProxies)->handle($request, function ($request) use ($trusted, $remote) {
                    $this->assertSame($trusted, $request->isSecure());
                    $this->assertSame($trusted ? '203.0.113.20' : $remote, $request->ip());

                    return response('ok');
                });
            }
        } finally {
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        }
    }

    public function test_production_rejects_unexpected_host(): void
    {
        $this->app->instance('env', 'production');
        config(['app.url' => 'https://horizonte.test']);
        try {
            $this->get('https://attacker.test/nosotros')->assertStatus(400);
            $this->get('https://horizonte.test/nosotros')->assertOk();
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    public function test_readiness_check_rejects_local_environment(): void
    {
        $this->artisan('security:check')->assertExitCode(1);
    }
}
