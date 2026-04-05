<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Models\StoredFile;
use App\Modules\Admin\File\Domain\Contracts\FileFolderRepositoryInterface;
use App\Modules\Admin\File\Domain\Contracts\StoredFileRepositoryInterface;
use App\Modules\Admin\File\Infrastructure\Persistence\Mappers\StoredFileMapper;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 文件应用服务：负责编排分组和文件业务。
 */
final class FileApplicationService extends ApplicationService
{
    public function __construct(
        private readonly FileFolderRepositoryInterface $fileFolderRepository,
        private readonly StoredFileRepositoryInterface $storedFileRepository,
        private readonly StoredFileMapper $storedFileMapper,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listFolders(): array
    {
        return $this->fileFolderRepository->listAll();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function createFolder(array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($payload): array {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                throw new ApiBusinessException(ApiCode::VALIDATION_ERROR, '分组名称不能为空');
            }

            $parentId = $payload['parentId'] ?? null;
            if ($parentId !== null && !$this->fileFolderRepository->exists((int) $parentId)) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '上级分组不存在');
            }

            $folder = $this->fileFolderRepository->create([
                'parentId' => $parentId,
                'name' => $name,
                'sort' => (int) ($payload['sort'] ?? 0),
            ]);

            return $this->mapFolder($folder);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function updateFolder(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload): array {
            $folder = $this->fileFolderRepository->findById($id);
            if (!$folder) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '分组不存在');
            }

            $parentId = $payload['parentId'] ?? $folder->parentId;
            if ($parentId !== null && (int) $parentId === $id) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '上级分组不能是自身');
            }
            if ($parentId !== null && !$this->fileFolderRepository->exists((int) $parentId)) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '上级分组不存在');
            }

            $nextName = array_key_exists('name', $payload)
                ? trim((string) $payload['name'])
                : (string) $folder->name;

            if ($nextName === '') {
                throw new ApiBusinessException(ApiCode::VALIDATION_ERROR, '分组名称不能为空');
            }

            $updated = $this->fileFolderRepository->update($folder, [
                'parentId' => $parentId,
                'name' => $nextName,
                'sort' => array_key_exists('sort', $payload) ? (int) $payload['sort'] : $folder->sort,
            ]);

            return $this->mapFolder($updated);
        });
    }

    #[WithTransaction]
    public function deleteFolder(int $id, mixed $moveToRaw = null): bool
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $moveToRaw): bool {
            $folder = $this->fileFolderRepository->findById($id);
            if (!$folder) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '分组不存在');
            }

            if ($this->fileFolderRepository->hasChildren($id)) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '请先删除子分组');
            }

            $fileCount = $this->storedFileRepository->countByFolderId($id);
            $moveTo = $this->parseMoveTo($moveToRaw);

            if ($fileCount > 0 && $moveToRaw === null) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '分组下存在文件，请先选择目标分组');
            }

            if ($moveTo !== null && $moveTo === $id) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '目标分组不能是当前分组');
            }

            if ($moveTo !== null && !$this->fileFolderRepository->exists($moveTo)) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '目标分组不存在');
            }

            if ($fileCount > 0) {
                $this->storedFileRepository->moveByFolderId($id, $moveTo);
            }

            $this->fileFolderRepository->delete($folder);

            return true;
        });
    }

    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function listFiles(array $query): array
    {
        return $this->storedFileRepository->paginate($query);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    #[WithTransaction]
    public function uploadFiles(array $files, ?int $folderId, ?string $source, ?AdminUser $user): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($files, $folderId, $source, $user): array {
            if ($folderId !== null && !$this->fileFolderRepository->exists($folderId)) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '分组不存在');
            }

            $sourceValue = in_array($source, ['ADMIN', 'USER'], true) ? $source : 'ADMIN';
            $disk = Storage::disk('public');
            $rows = [];

            /** @var UploadedFile $uploaded */
            foreach ($files as $uploaded) {
                $originalName = $this->normalizeOriginalName((string) $uploaded->getClientOriginalName());
                $ext = strtolower((string) $uploaded->getClientOriginalExtension());
                $mimeType = $uploaded->getClientMimeType();
                $kind = $this->detectKind($mimeType, $ext);
                $dir = 'uploads/'.date('Y/m/d');
                $storedName = Str::uuid()->toString().($ext !== '' ? ".{$ext}" : '');
                $relativePath = $disk->putFileAs($dir, $uploaded, $storedName);

                if (!$relativePath) {
                    throw new ApiBusinessException(ApiCode::SERVER_ERROR, '文件上传失败');
                }

                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $model = $this->storedFileRepository->create([
                    'folderId' => $folderId,
                    'source' => $sourceValue,
                    'kind' => $kind,
                    'name' => $nameWithoutExt !== '' ? $nameWithoutExt : $originalName,
                    'originalName' => $originalName,
                    'ext' => $ext !== '' ? $ext : null,
                    'mimeType' => $mimeType ?: null,
                    'size' => (int) $uploaded->getSize(),
                    'relativePath' => $relativePath,
                    'url' => $disk->url($relativePath),
                    'createdById' => $user?->id,
                    'createdByName' => $user?->username,
                ]);

                $rows[] = $this->mapFile($model);
            }

            return $rows;
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function updateFile(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload): array {
            $file = $this->storedFileRepository->findById($id);
            if (!$file) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '文件不存在');
            }

            $nextFolderId = array_key_exists('folderId', $payload) ? $payload['folderId'] : $file->folderId;
            if ($nextFolderId !== null && !$this->fileFolderRepository->exists((int) $nextFolderId)) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '目标分组不存在');
            }

            $nextName = array_key_exists('name', $payload)
                ? trim((string) $payload['name'])
                : (string) $file->name;

            if ($nextName === '') {
                throw new ApiBusinessException(ApiCode::VALIDATION_ERROR, '文件名不能为空');
            }

            $updated = $this->storedFileRepository->update($file, [
                'folderId' => $nextFolderId,
                'name' => $nextName,
            ]);

            return $this->mapFile($updated);
        });
    }

    #[WithTransaction]
    public function deleteFile(int $id): bool
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id): bool {
            $file = $this->storedFileRepository->findById($id);
            if (!$file) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '文件不存在');
            }

            Storage::disk('public')->delete((string) $file->relativePath);
            $this->storedFileRepository->delete($file);

            return true;
        });
    }

    /**
     * @param array<int> $ids
     */
    #[WithTransaction]
    public function batchDeleteFiles(array $ids): bool
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($ids): bool {
            $uniqueIds = array_values(array_unique(array_map('intval', $ids)));
            foreach ($uniqueIds as $id) {
                $file = $this->storedFileRepository->findById($id);
                if ($file) {
                    Storage::disk('public')->delete((string) $file->relativePath);
                }
            }
            $this->storedFileRepository->deleteByIds($uniqueIds);

            return true;
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function mapFolder(object $folder): array
    {
        return [
            'id' => (int) $folder->id,
            'parentId' => $folder->parentId !== null ? (int) $folder->parentId : null,
            'name' => (string) $folder->name,
            'sort' => (int) $folder->sort,
            'createdAt' => (string) $folder->createdAt,
            'updatedAt' => (string) $folder->updatedAt,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function mapFile(StoredFile $file): array
    {
        return $this->storedFileMapper->toArray($file);
    }

    private function detectKind(?string $mimeType, string $ext): string
    {
        $mime = strtolower((string) $mimeType);
        $extLower = strtolower($ext);

        if (str_starts_with($mime, 'image/')) {
            return 'IMAGE';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'VIDEO';
        }

        if (in_array($extLower, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'IMAGE';
        }

        if (in_array($extLower, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'], true)) {
            return 'VIDEO';
        }

        return 'FILE';
    }

    private function normalizeOriginalName(string $name): string
    {
        if ($name === '') {
            return 'unnamed';
        }

        if (mb_check_encoding($name, 'UTF-8')) {
            return $name;
        }

        $converted = @mb_convert_encoding($name, 'UTF-8', 'GBK,GB2312,BIG5,ISO-8859-1');

        return is_string($converted) && $converted !== '' ? $converted : $name;
    }

    private function parseMoveTo(mixed $moveToRaw): ?int
    {
        if ($moveToRaw === null || $moveToRaw === '' || $moveToRaw === 'ROOT') {
            return null;
        }

        $raw = trim((string) $moveToRaw);
        if ($raw === '' || !ctype_digit($raw)) {
            throw new ApiBusinessException(ApiCode::BAD_REQUEST, 'moveTo 参数不合法');
        }

        return max(1, (int) $raw);
    }
}
