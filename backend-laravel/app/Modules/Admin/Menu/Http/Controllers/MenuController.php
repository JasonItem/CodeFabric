<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Modules\Admin\Menu\Application\Services\MenuApplicationService;
use App\Modules\Admin\Menu\Http\Requests\CreateMenuRequest;
use App\Modules\Admin\Menu\Http\Requests\UpdateMenuRequest;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('menus')]
#[OA\Tag(name: '菜单管理', description: '菜单列表、新增、编辑与删除')]
final class MenuController extends ApiController
{
    public function __construct(
        private readonly MenuApplicationService $menuApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:menu:list')]
    #[OA\Get(
        path: '/api/admin/menus',
        operationId: 'adminMenuList',
        description: '获取菜单列表',
        tags: ['菜单管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '获取成功',
                content: new OA\JsonContent(ref: '#/components/schemas/MenuListEnvelope')
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
        return $this->ok($this->menuApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:menu:add')]
    #[OperationLog(module: '菜单管理', action: '新增菜单')]
    #[OA\Post(
        path: '/api/admin/menus',
        operationId: 'adminMenuCreate',
        description: '新增菜单',
        tags: ['菜单管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MenuCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '新增成功',
                content: new OA\JsonContent(ref: '#/components/schemas/MenuItemEnvelope')
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
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function create(CreateMenuRequest $request)
    {
        return $this->ok($this->menuApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:menu:edit')]
    #[OperationLog(module: '菜单管理', action: '编辑菜单')]
    #[OA\Put(
        path: '/api/admin/menus/{id}',
        operationId: 'adminMenuUpdate',
        description: '编辑菜单',
        tags: ['菜单管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '菜单 ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MenuUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '编辑成功',
                content: new OA\JsonContent(ref: '#/components/schemas/MenuItemEnvelope')
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
                description: '菜单不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function update(int $id, UpdateMenuRequest $request)
    {
        return $this->ok($this->menuApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:menu:delete')]
    #[OperationLog(module: '菜单管理', action: '删除菜单')]
    #[OA\Delete(
        path: '/api/admin/menus/{id}',
        operationId: 'adminMenuDelete',
        description: '删除菜单',
        tags: ['菜单管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: '菜单 ID',
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
                response: 400,
                description: '存在子分组，不能删除',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
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
                description: '菜单不存在',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function delete(int $id)
    {
        $this->menuApplicationService->delete($id);

        return $this->ok(true);
    }
}
