<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Infrastructure\Persistence\Repositories;

use App\Models\StoredFile;
use App\Modules\Admin\File\Domain\Contracts\StoredFileRepositoryInterface;
use App\Modules\Admin\File\Infrastructure\Persistence\Mappers\StoredFileMapper;

/**
 * 基于 Eloquent 的文件仓储实现。
 */
final class EloquentStoredFileRepository implements StoredFileRepositoryInterface
{
    public function __construct(
        private readonly StoredFileMapper $storedFileMapper,
    ) {
    }

    public function paginate(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, min(200, (int) ($query['pageSize'] ?? 30)));

        $builder = StoredFile::query()
            ->with('folder')
            ->when(array_key_exists('folderId', $query) && $query['folderId'] !== null && $query['folderId'] !== '', fn ($q) => $q->where('folderId', (int) $query['folderId']))
            ->when(($query['keyword'] ?? '') !== '', function ($q) use ($query) {
                $keyword = trim((string) $query['keyword']);
                $q->where(function ($subQuery) use ($keyword): void {
                    $subQuery->where('name', 'like', "%{$keyword}%")
                        ->orWhere('originalName', 'like', "%{$keyword}%");
                });
            })
            ->when(($query['source'] ?? '') !== '', fn ($q) => $q->where('source', (string) $query['source']))
            ->when(($query['kind'] ?? '') !== '', fn ($q) => $q->where('kind', (string) $query['kind']))
            ->when(($query['startAt'] ?? '') !== '', fn ($q) => $q->where('createdAt', '>=', (string) $query['startAt']))
            ->when(($query['endAt'] ?? '') !== '', fn ($q) => $q->where('createdAt', '<=', (string) $query['endAt']));

        $total = (int) $builder->count();

        $rows = $builder
            ->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get()
            ->map(fn (StoredFile $file) => $this->storedFileMapper->toArray($file))
            ->values()
            ->all();

        return [
            'list' => $rows,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    public function findById(int $id): ?StoredFile
    {
        return StoredFile::query()->with('folder')->find($id);
    }

    public function create(array $payload): StoredFile
    {
        /** @var StoredFile $file */
        $file = StoredFile::query()->create($payload);
        $file->load('folder');

        return $file;
    }

    public function update(StoredFile $file, array $payload): StoredFile
    {
        $file->fill($payload);
        $file->save();
        $file->load('folder');

        return $file;
    }

    public function delete(StoredFile $file): void
    {
        $file->delete();
    }

    public function deleteByIds(array $ids): int
    {
        return StoredFile::query()->whereIn('id', $ids)->delete();
    }

    public function countByFolderId(int $folderId): int
    {
        return (int) StoredFile::query()->where('folderId', $folderId)->count();
    }

    public function moveByFolderId(int $fromFolderId, ?int $toFolderId): int
    {
        return StoredFile::query()->where('folderId', $fromFolderId)->update([
            'folderId' => $toFolderId,
            'updatedAt' => now(),
        ]);
    }

}
