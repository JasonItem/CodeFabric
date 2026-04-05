<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Infrastructure\Persistence\Repositories;

use App\Models\FileFolder;
use App\Modules\Admin\File\Domain\Contracts\FileFolderRepositoryInterface;

/**
 * 基于 Eloquent 的文件分组仓储实现。
 */
final class EloquentFileFolderRepository implements FileFolderRepositoryInterface
{
    public function listAll(): array
    {
        return FileFolder::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static fn (FileFolder $folder) => [
                'id' => (int) $folder->id,
                'parentId' => $folder->parentId !== null ? (int) $folder->parentId : null,
                'name' => (string) $folder->name,
                'sort' => (int) $folder->sort,
                'createdAt' => (string) $folder->createdAt,
                'updatedAt' => (string) $folder->updatedAt,
            ])
            ->values()
            ->all();
    }

    public function findById(int $id): ?FileFolder
    {
        return FileFolder::query()->find($id);
    }

    public function create(array $payload): FileFolder
    {
        /** @var FileFolder $folder */
        $folder = FileFolder::query()->create($payload);

        return $folder;
    }

    public function update(FileFolder $folder, array $payload): FileFolder
    {
        $folder->fill($payload);
        $folder->save();

        return $folder;
    }

    public function delete(FileFolder $folder): void
    {
        $folder->delete();
    }

    public function hasChildren(int $folderId): bool
    {
        return FileFolder::query()->where('parentId', $folderId)->exists();
    }

    public function exists(int $folderId): bool
    {
        return FileFolder::query()->whereKey($folderId)->exists();
    }
}

