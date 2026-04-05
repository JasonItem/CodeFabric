<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Attributes\WithRedisLock;
use App\Attributes\WithTransaction;
use App\CrossCutting\RedisLockHandler;
use App\CrossCutting\TransactionHandler;
use ReflectionMethod;

/**
 * 应用服务基类。
 *
 * 约束：
 * - 仅在 ApplicationService 方法上声明 WithTransaction / WithRedisLock；
 * - 通过本基类统一执行横切逻辑，避免在 Controller 或 Repository 里处理事务和锁。
 */
abstract class ApplicationService
{
    protected function callWithAspects(string $method, callable $callback): mixed
    {
        $reflection = new ReflectionMethod($this, $method);

        $transactionAttr = $this->firstAttribute($reflection, WithTransaction::class);
        $lockAttr = $this->firstAttribute($reflection, WithRedisLock::class);

        $runner = static fn () => $callback();

        if ($transactionAttr instanceof WithTransaction) {
            $runner = fn () => app(TransactionHandler::class)->run($transactionAttr, $runner);
        }

        if ($lockAttr instanceof WithRedisLock) {
            $runner = fn () => app(RedisLockHandler::class)->run($lockAttr, $this->lockReplacements(), $runner);
        }

        return $runner();
    }

    /**
     * @param class-string $attributeClass
     */
    private function firstAttribute(ReflectionMethod $method, string $attributeClass): ?object
    {
        $attributes = $method->getAttributes($attributeClass);
        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * @return array<string,string>
     */
    private function lockReplacements(): array
    {
        $request = request();
        $user = $request?->attributes?->get('adminUser');

        return [
            '{uid}' => (string) ($user?->id ?? ''),
            '{path}' => (string) ($request?->path() ?? ''),
        ];
    }
}

