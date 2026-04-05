<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\LoginLog\Application\Services\LoginLogApplicationService;
use App\Modules\Admin\LoginLog\Http\Requests\ClearLoginLogRequest;
use App\Modules\Admin\LoginLog\Http\Requests\ListLoginLogRequest;
use App\Shared\Http\ApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 登录日志控制器：协议层。
 */
#[Prefix('login-logs')]
final class LoginLogController extends ApiController
{
    public function __construct(
        private readonly LoginLogApplicationService $loginLogApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:login-log:list')]
    public function list(ListLoginLogRequest $request)
    {
        return $this->ok($this->loginLogApplicationService->list($request->validated()));
    }

    #[Delete('')]
    #[ApiAuth(permission: 'system:login-log:delete')]
    #[OperationLog(module: '登录日志', action: '清理登录日志')]
    public function clear(ClearLoginLogRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($request->validated()['ids'] ?? []))));

        return $this->ok($this->loginLogApplicationService->clear($ids));
    }
}
