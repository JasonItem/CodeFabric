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
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 文件管理控制器：仅处理 HTTP 协议输入输出。
 */
#[Prefix('files')]
#[OA\Tag(name: '文件管理', description: '文件分组、上传、查询、编辑与删除')]
final class FileController extends ApiController
{
    public function __construct(
        private readonly FileApplicationService $fileApplicationService,
    ) {
    }

    #[Get('folders')]
    #[ApiAuth(permission: 'system:file:list')]
    #[OA\Get(
        path: '/api/admin/files/folders',
        operationId: 'adminFileFolderList',
        description: '获取文件分组列表',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/FileFolderListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function listFolders()
    {
        return $this->ok($this->fileApplicationService->listFolders());
    }

    #[Post('folders')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '新增分组')]
    #[OA\Post(
        path: '/api/admin/files/folders',
        operationId: 'adminFileFolderCreate',
        description: '新增文件分组',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FileFolderCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: '新增成功', content: new OA\JsonContent(ref: '#/components/schemas/FileFolderItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '上级分组不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function createFolder(CreateFolderRequest $request)
    {
        return $this->ok($this->fileApplicationService->createFolder($request->validated()));
    }

    #[Put('folders/{id}')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '编辑分组')]
    #[OA\Put(
        path: '/api/admin/files/folders/{id}',
        operationId: 'adminFileFolderUpdate',
        description: '编辑文件分组',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '分组 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FileFolderUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: '编辑成功', content: new OA\JsonContent(ref: '#/components/schemas/FileFolderItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '分组或上级分组不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function updateFolder(int $id, UpdateFolderRequest $request)
    {
        return $this->ok($this->fileApplicationService->updateFolder($id, $request->validated()));
    }

    #[Delete('folders/{id}')]
    #[ApiAuth(permission: 'system:file:folder')]
    #[OperationLog(module: '文件管理', action: '删除分组')]
    #[OA\Delete(
        path: '/api/admin/files/folders/{id}',
        operationId: 'adminFileFolderDelete',
        description: '删除文件分组，可通过 moveTo 指定文件迁移目标（ROOT 或分组 ID）',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '分组 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'moveTo', in: 'query', required: false, description: '迁移目标分组，ROOT 表示根目录', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 400, description: '存在子分组或参数不合法', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '分组或迁移目标不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function deleteFolder(int $id, Request $request)
    {
        return $this->ok($this->fileApplicationService->deleteFolder($id, $request->query('moveTo')));
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:file:list')]
    #[OA\Get(
        path: '/api/admin/files',
        operationId: 'adminFileList',
        description: '分页查询文件列表',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: '页码', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'pageSize', in: 'query', required: false, description: '每页数量', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 200)),
            new OA\Parameter(name: 'folderId', in: 'query', required: false, description: '分组 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'keyword', in: 'query', required: false, description: '文件名关键字', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'source', in: 'query', required: false, description: '来源', schema: new OA\Schema(type: 'string', enum: ['ADMIN', 'USER'])),
            new OA\Parameter(name: 'kind', in: 'query', required: false, description: '文件类型', schema: new OA\Schema(type: 'string', enum: ['IMAGE', 'VIDEO', 'FILE'])),
            new OA\Parameter(name: 'startAt', in: 'query', required: false, description: '开始时间', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'endAt', in: 'query', required: false, description: '结束时间', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '查询成功', content: new OA\JsonContent(ref: '#/components/schemas/FileListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function listFiles(ListFilesRequest $request)
    {
        return $this->ok($this->fileApplicationService->listFiles($request->validated()));
    }

    #[Post('upload')]
    #[ApiAuth(permission: 'system:file:upload')]
    #[OperationLog(module: '文件管理', action: '上传文件')]
    #[OA\Post(
        path: '/api/admin/files/upload',
        operationId: 'adminFileUpload',
        description: '上传文件（multipart/form-data）',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/FileUploadRequest')
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '上传成功', content: new OA\JsonContent(ref: '#/components/schemas/FileUploadEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '分组不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
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
    #[OA\Put(
        path: '/api/admin/files/{id}',
        operationId: 'adminFileUpdate',
        description: '修改文件信息',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '文件 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FileUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: '修改成功', content: new OA\JsonContent(ref: '#/components/schemas/FileItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '文件或目标分组不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function updateFile(int $id, UpdateFileRequest $request)
    {
        return $this->ok($this->fileApplicationService->updateFile($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:file:delete')]
    #[OperationLog(module: '文件管理', action: '删除文件')]
    #[OA\Delete(
        path: '/api/admin/files/{id}',
        operationId: 'adminFileDelete',
        description: '删除单个文件',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '文件 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '文件不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function deleteFile(int $id)
    {
        return $this->ok($this->fileApplicationService->deleteFile($id));
    }

    #[Post('batch-delete')]
    #[ApiAuth(permission: 'system:file:delete')]
    #[OperationLog(module: '文件管理', action: '批量删除文件')]
    #[OA\Post(
        path: '/api/admin/files/batch-delete',
        operationId: 'adminFileBatchDelete',
        description: '批量删除文件',
        tags: ['文件管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/FileBatchDeleteRequest')),
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function batchDelete(BatchDeleteFilesRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));

        return $this->ok($this->fileApplicationService->batchDeleteFiles($ids));
    }
}
