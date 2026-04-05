<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\OperationLog\Application\Services\OperationLogApplicationService;
use App\Modules\Admin\OperationLog\Http\Requests\ClearOperationLogRequest;
use App\Modules\Admin\OperationLog\Http\Requests\ListOperationLogRequest;
use App\Shared\Http\ApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 操作日志控制器：仅承担协议层职责。
 */
#[Prefix('operation-logs')]
final class OperationLogController extends ApiController
{
    public function __construct(
        private readonly OperationLogApplicationService $operationLogApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:operation-log:list')]
    public function list(ListOperationLogRequest $request)
    {
        return $this->ok($this->operationLogApplicationService->list($request->validated()));
    }

    #[Get('{id}')]
    #[ApiAuth(permission: 'system:operation-log:list')]
    public function detail(int $id)
    {
        return $this->ok($this->operationLogApplicationService->detail($id));
    }

    #[Delete('')]
    #[ApiAuth(permission: 'system:operation-log:delete')]
    #[OperationLog(module: '操作日志', action: '清理操作日志')]
    public function clear(ClearOperationLogRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($request->validated()['ids'] ?? []))));

        return $this->ok($this->operationLogApplicationService->clear($ids));
    }
}

