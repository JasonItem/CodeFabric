<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Modules\Admin\User\Application\Services\UserApplicationService;
use App\Modules\Admin\User\Http\Requests\CreateUserRequest;
use App\Modules\Admin\User\Http\Requests\UpdateUserRequest;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 用户管理控制器（协议层）。
 */
#[Prefix('users')]
#[OA\Tag(name: '用户管理', description: '用户列表、新增、编辑与删除')]
final class UserController extends ApiController
{
    public function __construct(
        private readonly UserApplicationService $userApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:user:list')]
    #[OA\Get(
        path: '/api/admin/users',
        operationId: 'adminUserList',
        description: '获取用户列表（含角色）',
        tags: ['用户管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '获取成功',
                content: new OA\JsonContent(ref: '#/components/schemas/UserListEnvelope')
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
        return $this->ok($this->userApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:user:add')]
    #[OperationLog(module: '用户管理', action: '新增用户')]
    #[OA\Post(
        path: '/api/admin/users',
        operationId: 'adminUserCreate',
        description: '新增用户',
        tags: ['用户管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '新增成功',
                content: new OA\JsonContent(ref: '#/components/schemas/UserItemEnvelope')
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
                description: '账号已存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function create(CreateUserRequest $request)
    {
        return $this->ok($this->userApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:user:edit')]
    #[OperationLog(module: '用户管理', action: '编辑用户')]
    #[OA\Put(
        path: '/api/admin/users/{id}',
        operationId: 'adminUserUpdate',
        description: '编辑用户',
        tags: ['用户管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '用户 ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '编辑成功',
                content: new OA\JsonContent(ref: '#/components/schemas/UserItemEnvelope')
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
                description: '用户不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 409,
                description: '账号已存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function update(int $id, UpdateUserRequest $request)
    {
        return $this->ok($this->userApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:user:delete')]
    #[OperationLog(module: '用户管理', action: '删除用户')]
    #[OA\Delete(
        path: '/api/admin/users/{id}',
        operationId: 'adminUserDelete',
        description: '删除用户',
        tags: ['用户管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '用户 ID',
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
                description: '用户不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function delete(int $id)
    {
        $this->userApplicationService->delete($id);

        return $this->ok(true);
    }
}
