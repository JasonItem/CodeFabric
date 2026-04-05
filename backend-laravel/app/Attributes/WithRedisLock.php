<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;

/**
 * Redis 锁注解。
 *
 * key 支持模板变量：
 * - {uid}: 当前登录用户ID
 * - {path}: 当前请求路径
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class WithRedisLock
{
    public function __construct(
        public readonly string $key,
        public readonly int $seconds = 5,
        public readonly int $waitSeconds = 0,
    ) {
    }
}
