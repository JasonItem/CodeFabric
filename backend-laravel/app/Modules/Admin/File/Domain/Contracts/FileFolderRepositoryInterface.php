<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Domain\Contracts;

use App\Models\FileFolder;

/**
 * 文件分组仓储接口。
 */
interface FileFolderRepositoryInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listAll(): array;

    public function findById(int $id): ?FileFolder;

    public function create(array $payload): FileFolder;

    public function update(FileFolder $folder, array $payload): FileFolder;

    public function delete(FileFolder $folder): void;

    public function hasChildren(int $folderId): bool;

    public function exists(int $folderId): bool;
}

