<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Domain\Contracts;

/**
 * 操作日志仓储接口。
 */
interface OperationLogRepositoryInterface
{
    public function record(array $attributes): void;

    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function paginate(array $query): array;

    /**
     * @return array<string,mixed>|null
     */
    public function detail(int $id): ?array;

    /**
     * @param array<int> $ids
     */
    public function deleteByIds(array $ids): int;

    public function deleteAll(): int;
}

