<?php

declare(strict_types=1);

namespace App\CrossCutting;

use App\Attributes\WithRedisLock;
use App\Enums\ApiCode;
use App\Shared\Exceptions\ApiBusinessException;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Redis 锁横切处理器。
 */
final class RedisLockHandler
{
    /**
     * @param array<string,string> $replacements
     */
    public function run(WithRedisLock $meta, array $replacements, Closure $callback): mixed
    {
        $key = strtr($meta->key, $replacements);

        try {
            $lock = Cache::store('redis')->lock($key, $meta->seconds);
            $acquired = $meta->waitSeconds > 0 ? $lock->block($meta->waitSeconds) : $lock->get();

            if (!$acquired) {
                throw new ApiBusinessException(ApiCode::REPEAT_SUBMIT, '请求过于频繁，请稍后重试');
            }

            try {
                return $callback();
            } finally {
                optional($lock)->release();
            }
        } catch (ApiBusinessException $e) {
            throw $e;
        } catch (\Throwable) {
            return $callback();
        }
    }
}
