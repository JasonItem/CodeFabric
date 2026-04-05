<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\File\Application\Services;

use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Models\FileFolder;
use App\Models\StoredFile;
use App\Modules\Admin\File\Application\Services\FileApplicationService;
use App\Modules\Admin\File\Domain\Contracts\FileFolderRepositoryInterface;
use App\Modules\Admin\File\Domain\Contracts\StoredFileRepositoryInterface;
use App\Modules\Admin\File\Infrastructure\Persistence\Mappers\StoredFileMapper;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FileApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function create_folder_rejects_missing_parent_folder(): void
    {
        $folderRepository = Mockery::mock(FileFolderRepositoryInterface::class);
        $folderRepository->shouldReceive('exists')->once()->with(99)->andReturn(false);
        $folderRepository->shouldNotReceive('create');

        $service = new FileApplicationService(
            $folderRepository,
            Mockery::mock(StoredFileRepositoryInterface::class),
            new StoredFileMapper(),
        );

        try {
            $service->createFolder(['name' => '文档', 'parentId' => 99]);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('上级分组不存在', $e->getMessage());
        }
    }

    #[Test]
    public function delete_folder_requires_move_target_when_files_exist(): void
    {
        $folder = $this->makeFolder(5, '图片');

        $folderRepository = Mockery::mock(FileFolderRepositoryInterface::class);
        $folderRepository->shouldReceive('findById')->once()->with(5)->andReturn($folder);
        $folderRepository->shouldReceive('hasChildren')->once()->with(5)->andReturn(false);
        $folderRepository->shouldNotReceive('delete');

        $fileRepository = Mockery::mock(StoredFileRepositoryInterface::class);
        $fileRepository->shouldReceive('countByFolderId')->once()->with(5)->andReturn(2);
        $fileRepository->shouldNotReceive('moveByFolderId');

        $service = new FileApplicationService($folderRepository, $fileRepository, new StoredFileMapper());

        try {
            $service->deleteFolder(5);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertSame('分组下存在文件，请先选择目标分组', $e->getMessage());
        }
    }

    #[Test]
    public function upload_files_stores_file_and_maps_created_row(): void
    {
        Storage::fake('public');

        $folderRepository = Mockery::mock(FileFolderRepositoryInterface::class);
        $folderRepository->shouldReceive('exists')->once()->with(3)->andReturn(true);

        $capturedPayload = null;
        $storedFile = $this->makeStoredFile(11, 'avatar', 'avatar.png', 'png', 'IMAGE', 3);

        $fileRepository = Mockery::mock(StoredFileRepositoryInterface::class);
        $fileRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $payload) use (&$capturedPayload): bool {
                $capturedPayload = $payload;
                return true;
            }))
            ->andReturnUsing(function (array $payload) use ($storedFile): StoredFile {
                $storedFile->folderId = $payload['folderId'];
                $storedFile->source = $payload['source'];
                $storedFile->kind = $payload['kind'];
                $storedFile->name = $payload['name'];
                $storedFile->originalName = $payload['originalName'];
                $storedFile->ext = $payload['ext'];
                $storedFile->mimeType = $payload['mimeType'];
                $storedFile->size = $payload['size'];
                $storedFile->relativePath = $payload['relativePath'];
                $storedFile->url = $payload['url'];
                $storedFile->createdById = $payload['createdById'];
                $storedFile->createdByName = $payload['createdByName'];
                return $storedFile;
            });

        $service = new FileApplicationService($folderRepository, $fileRepository, new StoredFileMapper());

        $user = new AdminUser();
        $user->id = 7;
        $user->username = 'admin';

        $file = UploadedFile::fake()->create('avatar.png', 12, 'image/png');
        $result = $service->uploadFiles([$file], 3, 'ADMIN', $user);

        $this->assertCount(1, $result);
        $this->assertSame('IMAGE', $result[0]['kind']);
        $this->assertSame(7, $result[0]['createdById']);
        $this->assertSame('ADMIN', $capturedPayload['source']);
        $this->assertSame('avatar.png', $capturedPayload['originalName']);
        Storage::disk('public')->assertExists($capturedPayload['relativePath']);
    }

    #[Test]
    public function update_file_rejects_empty_name(): void
    {
        $file = $this->makeStoredFile(9, 'report', 'report.pdf', 'pdf', 'FILE', null);

        $folderRepository = Mockery::mock(FileFolderRepositoryInterface::class);
        $folderRepository->shouldNotReceive('exists');

        $fileRepository = Mockery::mock(StoredFileRepositoryInterface::class);
        $fileRepository->shouldReceive('findById')->once()->with(9)->andReturn($file);
        $fileRepository->shouldNotReceive('update');

        $service = new FileApplicationService($folderRepository, $fileRepository, new StoredFileMapper());

        try {
            $service->updateFile(9, ['name' => '   ']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::VALIDATION_ERROR, $e->apiCode);
            $this->assertSame('文件名不能为空', $e->getMessage());
        }
    }

    #[Test]
    public function batch_delete_files_removes_storage_files_and_deletes_unique_ids(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/a.txt', 'a');
        Storage::disk('public')->put('uploads/b.txt', 'b');

        $fileA = $this->makeStoredFile(1, 'a', 'a.txt', 'txt', 'FILE', null);
        $fileA->relativePath = 'uploads/a.txt';
        $fileB = $this->makeStoredFile(2, 'b', 'b.txt', 'txt', 'FILE', null);
        $fileB->relativePath = 'uploads/b.txt';

        $folderRepository = Mockery::mock(FileFolderRepositoryInterface::class);

        $fileRepository = Mockery::mock(StoredFileRepositoryInterface::class);
        $fileRepository->shouldReceive('findById')->once()->with(1)->andReturn($fileA);
        $fileRepository->shouldReceive('findById')->once()->with(2)->andReturn($fileB);
        $fileRepository->shouldReceive('deleteByIds')->once()->with([1, 2])->andReturn(2);

        $service = new FileApplicationService($folderRepository, $fileRepository, new StoredFileMapper());
        $this->assertTrue($service->batchDeleteFiles([1, 2, 1]));

        Storage::disk('public')->assertMissing('uploads/a.txt');
        Storage::disk('public')->assertMissing('uploads/b.txt');
    }

    private function makeFolder(int $id, string $name): FileFolder
    {
        $folder = new FileFolder();
        $folder->id = $id;
        $folder->parentId = null;
        $folder->name = $name;
        $folder->sort = 0;
        $folder->createdAt = '2026-01-01 00:00:00';
        $folder->updatedAt = '2026-01-01 00:00:00';

        return $folder;
    }

    private function makeStoredFile(
        int $id,
        string $name,
        string $originalName,
        string $ext,
        string $kind,
        ?int $folderId,
    ): StoredFile {
        $file = new StoredFile();
        $file->id = $id;
        $file->folderId = $folderId;
        $file->source = 'ADMIN';
        $file->kind = $kind;
        $file->name = $name;
        $file->originalName = $originalName;
        $file->ext = $ext;
        $file->mimeType = 'application/octet-stream';
        $file->size = 10;
        $file->relativePath = 'uploads/tmp';
        $file->url = '/storage/uploads/tmp';
        $file->createdById = 1;
        $file->createdByName = 'admin';
        $file->createdAt = '2026-01-01 00:00:00';
        $file->updatedAt = '2026-01-01 00:00:00';

        return $file;
    }
}
