<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

abstract class AdminApiFeatureTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        config()->set('jwt.ttl', 604800);
        putenv('LOGIN_RATE_LIMIT_PER_MINUTE=1000');
        $_ENV['LOGIN_RATE_LIMIT_PER_MINUTE'] = '1000';
        $_SERVER['LOGIN_RATE_LIMIT_PER_MINUTE'] = '1000';

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ]);

        RateLimiter::clear('admin|127.0.0.1');
        RateLimiter::clear('ops|127.0.0.1');
    }

    protected function loginAndGetToken(string $username = 'admin', string $password = 'admin123'): string
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->fail('Login failed with status '.$response->getStatusCode().': '.$response->getContent());
        }

        return $this->extractCookieValue($response, 'admin_token');
    }

    protected function apiGetAs(string $uri, string $token, array $headers = []): TestResponse
    {
        return $this->withHeaders($this->authHeaders($token, $headers))
            ->getJson($uri);
    }

    protected function apiPostAs(string $uri, array $data, string $token, array $headers = []): TestResponse
    {
        return $this->withHeaders($this->authHeaders($token, $headers))
            ->postJson($uri, $data);
    }

    protected function apiPutAs(string $uri, array $data, string $token, array $headers = []): TestResponse
    {
        return $this->withHeaders($this->authHeaders($token, $headers))
            ->putJson($uri, $data);
    }

    protected function apiDeleteAs(string $uri, string $token, array $data = [], array $headers = []): TestResponse
    {
        return $this->withHeaders($this->authHeaders($token, $headers))
            ->deleteJson($uri, $data);
    }

    protected function authHeaders(string $token, array $headers = []): array
    {
        return array_merge([
            'Authorization' => 'Bearer '.$token,
        ], $headers);
    }

    private function extractCookieValue(TestResponse $response, string $cookieName): string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn (Cookie $cookie): bool => $cookie->getName() === $cookieName);

        $this->assertNotNull($cookie, "Cookie [{$cookieName}] was not set on the response.");

        return $cookie->getValue();
    }
}
