<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\OperationLog\Application\Services\OperationLogApplicationService;
use App\Modules\Admin\OperationLog\Http\Requests\ClearOperationLogRequest;
use App\Modules\Admin\OperationLog\Http\Requests\ListOperationLogRequest;
use App\Shared\Http\ApiController;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 操作日志控制器：仅承担协议层职责。
 */
#[Prefix('operation-logs')]
#[OA\Tag(name: '操作日志', description: '操作日志分页查询、详情与清理')]
final class OperationLogController extends ApiController
{
    public function __construct(
        private readonly OperationLogApplicationService $operationLogApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:operation-log:list')]
    #[OA\Get(
        path: '/api/admin/operation-logs',
        operationId: 'adminOperationLogList',
        description: '分页查询操作日志',
        tags: ['操作日志'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: true, description: '页码，从 1 开始', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'pageSize', in: 'query', required: true, description: '每页数量', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 200)),
            new OA\Parameter(name: 'path', in: 'query', required: false, description: '请求路径关键字', schema: new OA\Schema(type: 'string', maxLength: 255)),
            new OA\Parameter(name: 'module', in: 'query', required: false, description: '模块名称关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'username', in: 'query', required: false, description: '用户名关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'success', in: 'query', required: false, description: '是否成功（true/false/1/0）', schema: new OA\Schema(type: 'string', enum: ['true', 'false', '1', '0'])),
            new OA\Parameter(name: 'startTime', in: 'query', required: false, description: '开始时间', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'endTime', in: 'query', required: false, description: '结束时间', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '查询成功', content: new OA\JsonContent(ref: '#/components/schemas/OperationLogListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function list(ListOperationLogRequest $request)
    {
        return $this->ok($this->operationLogApplicationService->list($request->validated()));
    }

    #[Get('{id}')]
    #[ApiAuth(permission: 'system:operation-log:list')]
    #[OA\Get(
        path: '/api/admin/operation-logs/{id}',
        operationId: 'adminOperationLogDetail',
        description: '获取操作日志详情',
        tags: ['操作日志'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '日志 ID', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/OperationLogDetailEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 404, description: '操作日志不存在', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function detail(int $id)
    {
        return $this->ok($this->operationLogApplicationService->detail($id));
    }

    #[Delete('')]
    #[ApiAuth(permission: 'system:operation-log:delete')]
    #[OperationLog(module: '操作日志', action: '清理操作日志')]
    #[OA\Delete(
        path: '/api/admin/operation-logs',
        operationId: 'adminOperationLogClear',
        description: '清理操作日志，不传 ids 时清空全部',
        tags: ['操作日志'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/ClearByIdsRequest')),
        responses: [
            new OA\Response(response: 200, description: '清理成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function clear(ClearOperationLogRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($request->validated()['ids'] ?? []))));

        return $this->ok($this->operationLogApplicationService->clear($ids));
    }
}
