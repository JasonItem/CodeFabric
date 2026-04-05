<?php

declare(strict_types=1);

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * JWT 工具（HS256）。
 *
 * 统一基于 firebase/php-jwt 实现，避免自实现签名逻辑的安全风险。
 */
final class JwtToken
{
    public static function encode(array $payload, string $secret): string
    {
        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function decode(string $jwt, string $secret): ?array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            $payload = (array) $decoded;

            return is_array($payload) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }
}
