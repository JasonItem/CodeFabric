<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * JWT 配置读取与安全校验。
 */
final class JwtConfig
{
    /**
     * @var array<int,string>
     */
    private const WEAK_SECRETS = [
        '',
        'dev-secret-change-me',
        'change-me',
        'secret',
        'jwt-secret',
        '123456',
        'password',
    ];

    public function secret(): string
    {
        $secret = trim((string) config('jwt.secret', ''));
        if ($secret === '' || in_array($secret, self::WEAK_SECRETS, true)) {
            throw new RuntimeException('JWT_SECRET 未配置或过弱，请设置高强度密钥后重启服务');
        }

        if (mb_strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET 长度不足，至少 32 个字符');
        }

        return $secret;
    }

    public function ttl(): int
    {
        $ttl = (int) config('jwt.ttl', 0);
        if ($ttl <= 0) {
            throw new RuntimeException('JWT_EXPIRES_IN 配置非法，必须大于 0');
        }

        return $ttl;
    }

    public function validateOrFail(): void
    {
        $this->secret();
        $this->ttl();
    }
}

