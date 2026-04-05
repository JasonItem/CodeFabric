<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\JwtConfig;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class JwtConfigTest extends TestCase
{
    #[Test]
    public function it_returns_secret_and_ttl_when_config_is_valid(): void
    {
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        config()->set('jwt.ttl', 3600);

        $config = new JwtConfig();

        $this->assertSame('unit-test-secret-unit-test-secret-32', $config->secret());
        $this->assertSame(3600, $config->ttl());
    }

    #[Test]
    public function it_fails_on_weak_secret(): void
    {
        config()->set('jwt.secret', 'dev-secret-change-me');
        config()->set('jwt.ttl', 3600);

        $config = new JwtConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET 未配置或过弱');
        $config->validateOrFail();
    }

    #[Test]
    public function it_fails_on_invalid_ttl(): void
    {
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        config()->set('jwt.ttl', 0);

        $config = new JwtConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_EXPIRES_IN 配置非法');
        $config->validateOrFail();
    }
}

