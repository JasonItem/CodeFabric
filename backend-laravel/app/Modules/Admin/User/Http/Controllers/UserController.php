<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Modules\Admin\User\Application\Services\UserApplicationService;
use App\Modules\Admin\User\Http\Requests\CreateUserRequest;
use App\Modules\Admin\User\Http\Requests\UpdateUserRequest;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 用户管理控制器（协议层）。
 */
#[Prefix('users')]
final class UserController extends ApiController
{
    public function __construct(
        private readonly UserApplicationService $userApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:user:list')]
    public function list()
    {
        return $this->ok($this->userApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:user:add')]
    #[OperationLog(module: '用户管理', action: '新增用户')]
    public function create(CreateUserRequest $request)
    {
        return $this->ok($this->userApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:user:edit')]
    #[OperationLog(module: '用户管理', action: '编辑用户')]
    public function update(int $id, UpdateUserRequest $request)
    {
        return $this->ok($this->userApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:user:delete')]
    #[OperationLog(module: '用户管理', action: '删除用户')]
    public function delete(int $id)
    {
        $this->userApplicationService->delete($id);

        return $this->ok(true);
    }
}
