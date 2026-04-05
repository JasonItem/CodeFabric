<?php

declare(strict_types=1);

namespace App\Modules\Admin\File\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Models\AdminUser;
use App\Modules\Admin\File\Application\Services\FileApplicationService;
use App\Modules\Admin\File\Http\Requests\BatchDeleteFilesRequest;
use App\Modules\Admin\File\Http\Requests\CreateFolderRequest;
use App\Modules\Admin\File\Http\Requests\ListFilesRequest;
use App\Modules\Admin\File\Http\Requests\UpdateFileRequest;
use App\Modules\Admin\File\Http\Requests\UpdateFolderRequest;
use App\Modules\Admin\File\Http\Requests\UploadFilesRequest;
use App\Shared\Http\ApiController;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 文件管理控制器：仅处理 HTTP 协议输入输出。
 */
#[Prefix('files')]
final class FileController extends ApiController
{
    public function __construct(
        private readonly FileApplicationService $fileApplicationService,
    ) {
    }

    #[Get('folders')]
    #[ApiAuth(permission: 'system:file:list')]
    public function listFolders()
    {
        return $this->ok($this->fileApplicationService->listFolders());
    }

    #[Post('folders')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '新增分组')]
    public function createFolder(CreateFolderRequest $request)
    {
        return $this->ok($this->fileApplicationService->createFolder($request->validated()));
    }

    #[Put('folders/{id}')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '编辑分组')]
    public function updateFolder(int $id, UpdateFolderRequest $request)
    {
        return $this->ok($this->fileApplicationService->updateFolder($id, $request->validated()));
    }

    #[Delete('folders/{id}')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '删除分组')]
    public function deleteFolder(int $id, Request $request)
    {
        return $this->ok($this->fileApplicationService->deleteFolder($id, $request->query('moveTo')));
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:file:list')]
    public function listFiles(ListFilesRequest $request)
    {
        return $this->ok($this->fileApplicationService->listFiles($request->validated()));
    }

    #[Post('upload')]
    #[ApiAuth(permission: 'system:file:upload')]
    #[OperationLog(module: '文件管理', action: '上传文件')]
    public function upload(UploadFilesRequest $request)
    {
        /** @var AdminUser|null $user */
        $user = $request->attributes->get('adminUser');
        $files = $request->normalizedFiles();

        return $this->ok($this->fileApplicationService->uploadFiles(
            $files,
            $request->input('folderId') !== null ? (int) $request->input('folderId') : null,
            $request->input('source') ? (string) $request->input('source') : null,
            $user,
        ));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:file:edit')]
    #[OperationLog(module: '文件管理', action: '修改文件')]
    public function updateFile(int $id, UpdateFileRequest $request)
    {
        return $this->ok($this->fileApplicationService->updateFile($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:file:delete')]
    #[OperationLog(module: '文件管理', action: '删除文件')]
    public function deleteFile(int $id)
    {
        return $this->ok($this->fileApplicationService->deleteFile($id));
    }

    #[Post('batch-delete')]
    #[ApiAuth(permission: 'system:file:delete')]
    #[OperationLog(module: '文件管理', action: '批量删除文件')]
    public function batchDelete(BatchDeleteFilesRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));

        return $this->ok($this->fileApplicationService->batchDeleteFiles($ids));
    }
}
