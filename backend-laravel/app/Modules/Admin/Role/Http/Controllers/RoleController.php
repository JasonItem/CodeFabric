<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Modules\Admin\Role\Application\Services\RoleApplicationService;
use App\Modules\Admin\Role\Http\Requests\AssignPermissionsRequest;
use App\Modules\Admin\Role\Http\Requests\CreateRoleRequest;
use App\Modules\Admin\Role\Http\Requests\UpdateRoleRequest;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('roles')]
final class RoleController extends ApiController
{
    public function __construct(
        private readonly RoleApplicationService $roleApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:role:list')]
    public function list()
    {
        return $this->ok($this->roleApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:role:add')]
    #[OperationLog(module: '角色管理', action: '新增角色')]
    public function create(CreateRoleRequest $request)
    {
        return $this->ok($this->roleApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:role:edit')]
    #[OperationLog(module: '角色管理', action: '编辑角色')]
    public function update(int $id, UpdateRoleRequest $request)
    {
        return $this->ok($this->roleApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:role:delete')]
    #[OperationLog(module: '角色管理', action: '删除角色')]
    public function delete(int $id)
    {
        $this->roleApplicationService->delete($id);

        return $this->ok(true);
    }

    #[Post('{id}/permissions')]
    #[ApiAuth(permission: 'system:role:assign')]
    #[OperationLog(module: '角色管理', action: '分配权限')]
    public function assignPermissions(int $id, AssignPermissionsRequest $request)
    {
        $this->roleApplicationService->assignPermissions($id, (array) $request->validated('menuIds'));

        return $this->ok(true);
    }
}
