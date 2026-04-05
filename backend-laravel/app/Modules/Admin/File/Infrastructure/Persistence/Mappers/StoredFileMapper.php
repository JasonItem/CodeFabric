<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Infrastructure\Persistence\Mappers;

use App\Models\StoredFile;

/**
 * StoredFile 统一输出映射器。
 */
final class StoredFileMapper
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(StoredFile $file): array
    {
        $folder = $file->relationLoaded('folder') ? $file->folder : null;

        return [
            'id' => (int) $file->id,
            'folderId' => $file->folderId !== null ? (int) $file->folderId : null,
            'source' => (string) $file->source,
            'kind' => (string) $file->kind,
            'name' => (string) $file->name,
            'originalName' => (string) $file->originalName,
            'ext' => $file->ext,
            'mimeType' => $file->mimeType,
            'size' => (int) $file->size,
            'relativePath' => (string) $file->relativePath,
            'url' => (string) $file->url,
            'createdById' => $file->createdById !== null ? (int) $file->createdById : null,
            'createdByName' => $file->createdByName,
            'createdAt' => (string) $file->createdAt,
            'updatedAt' => (string) $file->updatedAt,
            'folder' => $folder ? [
                'id' => (int) $folder->id,
                'parentId' => $folder->parentId !== null ? (int) $folder->parentId : null,
                'name' => (string) $folder->name,
                'sort' => (int) $folder->sort,
                'createdAt' => (string) $folder->createdAt,
                'updatedAt' => (string) $folder->updatedAt,
            ] : null,
        ];
    }
}

