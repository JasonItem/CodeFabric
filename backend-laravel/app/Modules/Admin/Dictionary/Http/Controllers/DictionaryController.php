<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\Dictionary\Application\Services\DictionaryApplicationService;
use App\Modules\Admin\Dictionary\Http\Requests\CreateDictItemRequest;
use App\Modules\Admin\Dictionary\Http\Requests\CreateDictTypeRequest;
use App\Modules\Admin\Dictionary\Http\Requests\UpdateDictItemRequest;
use App\Modules\Admin\Dictionary\Http\Requests\UpdateDictTypeRequest;
use App\Shared\Http\ApiController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 字典管理控制器：仅承担协议层职责。
 */
#[Prefix('dictionaries')]
#[OA\Tag(name: '字典管理', description: '字典类型与字典项管理')]
final class DictionaryController extends ApiController
{
    public function __construct(
        private readonly DictionaryApplicationService $dictionaryApplicationService,
    ) {
    }

    #[Get('types')]
    #[ApiAuth(permission: 'system:dict:list')]
    #[OA\Get(
        path: '/api/admin/dictionaries/types',
        operationId: 'adminDictionaryTypeList',
        description: '获取字典类型列表',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', required: false, description: '名称/编码关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/DictTypeListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function listTypes(Request $request)
    {
        return $this->ok(
            $this->dictionaryApplicationService->listTypes($request->query('keyword'))
        );
    }

    #[Post('types')]
    #[ApiAuth(permission: 'system:dict:add')]
    #[OperationLog(module: '字典管理', action: '新增字典类型')]
    #[OA\Post(
        path: '/api/admin/dictionaries/types',
        operationId: 'adminDictionaryTypeCreate',
        description: '新增字典类型',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DictTypeCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: '新增成功', content: new OA\JsonContent(ref: '#/components/schemas/DictTypeItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 409, description: '字典类型编码已存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function createType(CreateDictTypeRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->createType($request->validated()));
    }

    #[Put('types/{id}')]
    #[ApiAuth(permission: 'system:dict:edit')]
    #[OperationLog(module: '字典管理', action: '编辑字典类型')]
    #[OA\Put(
        path: '/api/admin/dictionaries/types/{id}',
        operationId: 'adminDictionaryTypeUpdate',
        description: '编辑字典类型',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '字典类型 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DictTypeUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: '编辑成功', content: new OA\JsonContent(ref: '#/components/schemas/DictTypeItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典类型不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 409, description: '字典类型编码已存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function updateType(int $id, UpdateDictTypeRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->updateType($id, $request->validated()));
    }

    #[Delete('types/{id}')]
    #[ApiAuth(permission: 'system:dict:delete')]
    #[OperationLog(module: '字典管理', action: '删除字典类型')]
    #[OA\Delete(
        path: '/api/admin/dictionaries/types/{id}',
        operationId: 'adminDictionaryTypeDelete',
        description: '删除字典类型',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '字典类型 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典类型不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function deleteType(int $id)
    {
        $this->dictionaryApplicationService->deleteType($id);

        return $this->ok(true);
    }

    #[Get('types/{typeId}/items')]
    #[ApiAuth(permission: 'system:dict:list')]
    #[OA\Get(
        path: '/api/admin/dictionaries/types/{typeId}/items',
        operationId: 'adminDictionaryItemList',
        description: '获取字典项列表',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'typeId', in: 'path', required: true, description: '字典类型 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'keyword', in: 'query', required: false, description: '标签/值关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/DictItemListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典类型不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function listItems(int $typeId, Request $request)
    {
        return $this->ok(
            $this->dictionaryApplicationService->listItems($typeId, $request->query('keyword'))
        );
    }

    #[Post('types/{typeId}/items')]
    #[ApiAuth(permission: 'system:dict:add')]
    #[OperationLog(module: '字典管理', action: '新增字典项')]
    #[OA\Post(
        path: '/api/admin/dictionaries/types/{typeId}/items',
        operationId: 'adminDictionaryItemCreate',
        description: '新增字典项',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'typeId', in: 'path', required: true, description: '字典类型 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DictItemCreateRequest')),
        responses: [
            new OA\Response(response: 200, description: '新增成功', content: new OA\JsonContent(ref: '#/components/schemas/DictItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典类型不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function createItem(int $typeId, CreateDictItemRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->createItem($typeId, $request->validated()));
    }

    #[Put('items/{id}')]
    #[ApiAuth(permission: 'system:dict:edit')]
    #[OperationLog(module: '字典管理', action: '编辑字典项')]
    #[OA\Put(
        path: '/api/admin/dictionaries/items/{id}',
        operationId: 'adminDictionaryItemUpdate',
        description: '编辑字典项',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '字典项 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DictItemUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: '编辑成功', content: new OA\JsonContent(ref: '#/components/schemas/DictItemEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典项不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function updateItem(int $id, UpdateDictItemRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->updateItem($id, $request->validated()));
    }

    #[Delete('items/{id}')]
    #[ApiAuth(permission: 'system:dict:delete')]
    #[OperationLog(module: '字典管理', action: '删除字典项')]
    #[OA\Delete(
        path: '/api/admin/dictionaries/items/{id}',
        operationId: 'adminDictionaryItemDelete',
        description: '删除字典项',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '字典项 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '字典项不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function deleteItem(int $id)
    {
        $this->dictionaryApplicationService->deleteItem($id);

        return $this->ok(true);
    }

    #[Get('options/{code}')]
    #[ApiAuth]
    #[OA\Get(
        path: '/api/admin/dictionaries/options/{code}',
        operationId: 'adminDictionaryOptionsByCode',
        description: '按字典类型编码获取可选项',
        tags: ['字典管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, description: '字典类型编码', schema: new OA\Schema(type: 'string', maxLength: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/DictOptionListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function optionsByCode(string $code)
    {
        return $this->ok($this->dictionaryApplicationService->optionsByCode($code));
    }
}
