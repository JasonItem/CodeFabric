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
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('roles')]
#[OA\Tag(name: '角色管理', description: '角色列表、新增、编辑、删除与权限分配')]
final class RoleController extends ApiController
{
    public function __construct(
        private readonly RoleApplicationService $roleApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:role:list')]
    #[OA\Get(
        path: '/api/admin/roles',
        operationId: 'adminRoleList',
        description: '获取角色列表',
        tags: ['角色管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '获取成功',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleListEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录或登录已失效',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function list()
    {
        return $this->ok($this->roleApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:role:add')]
    #[OperationLog(module: '角色管理', action: '新增角色')]
    #[OA\Post(
        path: '/api/admin/roles',
        operationId: 'adminRoleCreate',
        description: '新增角色',
        tags: ['角色管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RoleCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '新增成功',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleItemEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录或登录已失效',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 409,
                description: '角色编码已存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function create(CreateRoleRequest $request)
    {
        return $this->ok($this->roleApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:role:edit')]
    #[OperationLog(module: '角色管理', action: '编辑角色')]
    #[OA\Put(
        path: '/api/admin/roles/{id}',
        operationId: 'adminRoleUpdate',
        description: '编辑角色',
        tags: ['角色管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '角色 ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RoleUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '编辑成功',
                content: new OA\JsonContent(ref: '#/components/schemas/RoleItemEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录或登录已失效',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 409,
                description: '角色编码已存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function update(int $id, UpdateRoleRequest $request)
    {
        return $this->ok($this->roleApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:role:delete')]
    #[OperationLog(module: '角色管理', action: '删除角色')]
    #[OA\Delete(
        path: '/api/admin/roles/{id}',
        operationId: 'adminRoleDelete',
        description: '删除角色',
        tags: ['角色管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '角色 ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '删除成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录或登录已失效',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function delete(int $id)
    {
        $this->roleApplicationService->delete($id);

        return $this->ok(true);
    }

    #[Post('{id}/permissions')]
    #[ApiAuth(permission: 'system:role:assign')]
    #[OperationLog(module: '角色管理', action: '分配权限')]
    #[OA\Post(
        path: '/api/admin/roles/{id}/permissions',
        operationId: 'adminRoleAssignPermissions',
        description: '为角色分配菜单权限',
        tags: ['角色管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '角色 ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RoleAssignPermissionsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '分配成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录或登录已失效',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function assignPermissions(int $id, AssignPermissionsRequest $request)
    {
        $this->roleApplicationService->assignPermissions($id, (array) $request->validated('menuIds'));

        return $this->ok(true);
    }
}
