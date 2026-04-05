<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\Table\Application\Services\TableApplicationService;
use App\Modules\Admin\Table\Http\Requests\ExecuteTableSqlRequest;
use App\Shared\Http\ApiController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('tables')]
#[OA\Tag(name: '数据表管理', description: '数据表查询、导入导出与结构操作')]
final class TableController extends ApiController
{
    public function __construct(
        private readonly TableApplicationService $tableApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables',
        operationId: 'adminTableList',
        description: '分页查询数据表列表',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', required: false, description: '表名关键字', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: '页码', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'pageSize', in: 'query', required: false, description: '每页数量', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 200)),
        ],
        responses: [
            new OA\Response(response: 200, description: '查询成功', content: new OA\JsonContent(ref: '#/components/schemas/TableListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function list(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = min(max((int) $request->query('pageSize', 20), 1), 200);

        return $this->ok($this->tableApplicationService->list($keyword, $page, $pageSize));
    }

    #[Get('{tableName}/columns')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/{tableName}/columns',
        operationId: 'adminTableColumns',
        description: '获取表字段信息',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/TableColumnListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '数据表不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function columns(string $tableName)
    {
        return $this->ok($this->tableApplicationService->columns($tableName));
    }

    #[Get('{tableName}/create-sql')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/{tableName}/create-sql',
        operationId: 'adminTableCreateSql',
        description: '获取建表 SQL',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/TableCreateSqlEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 400, description: '无法获取建表 SQL', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function createSql(string $tableName)
    {
        return $this->ok($this->tableApplicationService->createSql($tableName));
    }

    #[Get('{tableName}/indexes')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/{tableName}/indexes',
        operationId: 'adminTableIndexes',
        description: '获取索引信息',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/TableIndexListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function indexes(string $tableName)
    {
        return $this->ok($this->tableApplicationService->indexes($tableName));
    }

    #[Get('{tableName}/foreign-keys')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/{tableName}/foreign-keys',
        operationId: 'adminTableForeignKeys',
        description: '获取外键信息',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/TableForeignKeyListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function foreignKeys(string $tableName)
    {
        return $this->ok($this->tableApplicationService->foreignKeys($tableName));
    }

    #[Get('{tableName}/export')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/{tableName}/export',
        operationId: 'adminTableExport',
        description: '导出单表 SQL',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '导出成功', content: new OA\JsonContent(ref: '#/components/schemas/TableExportEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 400, description: '导出失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function export(string $tableName)
    {
        return $this->ok($this->tableApplicationService->export($tableName));
    }

    #[Get('export-all')]
    #[ApiAuth(permission: 'system:table:list')]
    #[OA\Get(
        path: '/api/admin/tables/export-all',
        operationId: 'adminTableExportAll',
        description: '导出全库 SQL',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '导出成功', content: new OA\JsonContent(ref: '#/components/schemas/TableExportAllEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function exportAll()
    {
        return $this->ok($this->tableApplicationService->exportAll());
    }

    #[Post('import')]
    #[ApiAuth(permission: 'system:table:create')]
    #[OperationLog(module: '数据表管理', action: '导入 SQL')]
    #[OA\Post(
        path: '/api/admin/tables/import',
        operationId: 'adminTableImport',
        description: '导入 SQL 文件（multipart/form-data）',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/TableImportRequest')
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '导入成功', content: new OA\JsonContent(ref: '#/components/schemas/TableImportEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 409, description: '数据表已存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'mode' => ['nullable', 'in:strict,skip-create'],
        ]);

        $file = $request->file('file');
        return $this->ok($this->tableApplicationService->importSqlFile($file, (string) $request->input('mode', 'skip-create')));
    }

    #[Post('create')]
    #[ApiAuth(permission: 'system:table:create')]
    #[OperationLog(module: '数据表管理', action: '创建数据表')]
    #[OA\Post(
        path: '/api/admin/tables/create',
        operationId: 'adminTableCreateBySql',
        description: '执行 CREATE TABLE SQL',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TableExecuteSqlRequest')),
        responses: [
            new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 400, description: 'SQL 不合法', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function createBySql(ExecuteTableSqlRequest $request)
    {
        return $this->ok($this->tableApplicationService->createBySql((string) $request->validated('sql')));
    }

    #[Post('alter')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '修改数据表')]
    #[OA\Post(
        path: '/api/admin/tables/alter',
        operationId: 'adminTableAlterBySql',
        description: '执行 ALTER TABLE SQL',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TableExecuteSqlRequest')),
        responses: [
            new OA\Response(response: 200, description: '修改成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 400, description: 'SQL 不合法', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function alterBySql(ExecuteTableSqlRequest $request)
    {
        return $this->ok($this->tableApplicationService->alterBySql((string) $request->validated('sql')));
    }

    #[Delete('{tableName}')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '删除数据表')]
    #[OA\Delete(
        path: '/api/admin/tables/{tableName}',
        operationId: 'adminTableDelete',
        description: '删除数据表',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 400, description: '参数不合法', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function delete(string $tableName)
    {
        return $this->ok($this->tableApplicationService->remove($tableName));
    }

    #[Post('{tableName}/truncate')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '清空数据表')]
    #[OA\Post(
        path: '/api/admin/tables/{tableName}/truncate',
        operationId: 'adminTableTruncate',
        description: '清空数据表数据',
        tags: ['数据表管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tableName', in: 'path', required: true, description: '数据表名', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '清空成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 400, description: '参数不合法', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function truncate(string $tableName)
    {
        return $this->ok($this->tableApplicationService->truncate($tableName));
    }
}
