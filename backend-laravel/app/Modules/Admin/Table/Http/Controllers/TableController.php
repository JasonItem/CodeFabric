<?php

declare(strict_types=1);

namespace App\Modules\Admin\Table\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\Table\Application\Services\TableApplicationService;
use App\Modules\Admin\Table\Http\Requests\ExecuteTableSqlRequest;
use App\Shared\Http\ApiController;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('tables')]
final class TableController extends ApiController
{
    public function __construct(
        private readonly TableApplicationService $tableApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:table:list')]
    public function list(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $page = max((int) $request->query('page', 1), 1);
        $pageSize = min(max((int) $request->query('pageSize', 20), 1), 200);

        return $this->ok($this->tableApplicationService->list($keyword, $page, $pageSize));
    }

    #[Get('{tableName}/columns')]
    #[ApiAuth(permission: 'system:table:list')]
    public function columns(string $tableName)
    {
        return $this->ok($this->tableApplicationService->columns($tableName));
    }

    #[Get('{tableName}/create-sql')]
    #[ApiAuth(permission: 'system:table:list')]
    public function createSql(string $tableName)
    {
        return $this->ok($this->tableApplicationService->createSql($tableName));
    }

    #[Get('{tableName}/indexes')]
    #[ApiAuth(permission: 'system:table:list')]
    public function indexes(string $tableName)
    {
        return $this->ok($this->tableApplicationService->indexes($tableName));
    }

    #[Get('{tableName}/foreign-keys')]
    #[ApiAuth(permission: 'system:table:list')]
    public function foreignKeys(string $tableName)
    {
        return $this->ok($this->tableApplicationService->foreignKeys($tableName));
    }

    #[Get('{tableName}/export')]
    #[ApiAuth(permission: 'system:table:list')]
    public function export(string $tableName)
    {
        return $this->ok($this->tableApplicationService->export($tableName));
    }

    #[Get('export-all')]
    #[ApiAuth(permission: 'system:table:list')]
    public function exportAll()
    {
        return $this->ok($this->tableApplicationService->exportAll());
    }

    #[Post('import')]
    #[ApiAuth(permission: 'system:table:create')]
    #[OperationLog(module: '数据表管理', action: '导入 SQL')]
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
    public function createBySql(ExecuteTableSqlRequest $request)
    {
        return $this->ok($this->tableApplicationService->createBySql((string) $request->validated('sql')));
    }

    #[Post('alter')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '修改数据表')]
    public function alterBySql(ExecuteTableSqlRequest $request)
    {
        return $this->ok($this->tableApplicationService->alterBySql((string) $request->validated('sql')));
    }

    #[Delete('{tableName}')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '删除数据表')]
    public function delete(string $tableName)
    {
        return $this->ok($this->tableApplicationService->remove($tableName));
    }

    #[Post('{tableName}/truncate')]
    #[ApiAuth(permission: 'system:table:edit')]
    #[OperationLog(module: '数据表管理', action: '清空数据表')]
    public function truncate(string $tableName)
    {
        return $this->ok($this->tableApplicationService->truncate($tableName));
    }
}

