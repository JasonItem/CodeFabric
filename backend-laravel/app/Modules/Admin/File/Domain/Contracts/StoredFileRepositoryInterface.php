<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Domain\Contracts;

use App\Models\StoredFile;

/**
 * 文件仓储接口。
 */
interface StoredFileRepositoryInterface
{
    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function paginate(array $query): array;

    public function findById(int $id): ?StoredFile;

    /**
     * @param array<string,mixed> $payload
     */
    public function create(array $payload): StoredFile;

    /**
     * @param array<string,mixed> $payload
     */
    public function update(StoredFile $file, array $payload): StoredFile;

    public function delete(StoredFile $file): void;

    /**
     * @param array<int> $ids
     */
    public function deleteByIds(array $ids): int;

    public function countByFolderId(int $folderId): int;

    public function moveByFolderId(int $fromFolderId, ?int $toFolderId): int;
}

