<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Domain\Contracts;

/**
 * 登录日志仓储契约。
 */
interface LoginLogRepositoryInterface
{
    /**
     * @param array<string,mixed> $attributes
     */
    public function record(array $attributes): void;

    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function paginate(array $query): array;

    /**
     * @param array<int> $ids
     */
    public function deleteByIds(array $ids): int;

    public function deleteAll(): int;
}
