<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\LoginLog\Application\Services\LoginLogApplicationService;
use App\Modules\Admin\LoginLog\Http\Requests\ClearLoginLogRequest;
use App\Modules\Admin\LoginLog\Http\Requests\ListLoginLogRequest;
use App\Shared\Http\ApiController;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 登录日志控制器：协议层。
 */
#[Prefix('login-logs')]
#[OA\Tag(name: '登录日志', description: '登录日志分页查询与清理')]
final class LoginLogController extends ApiController
{
    public function __construct(
        private readonly LoginLogApplicationService $loginLogApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:login-log:list')]
    #[OA\Get(
        path: '/api/admin/login-logs',
        operationId: 'adminLoginLogList',
        description: '分页查询登录日志',
        tags: ['登录日志'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: true, description: '页码，从 1 开始', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'pageSize', in: 'query', required: true, description: '每页数量', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 200)),
            new OA\Parameter(name: 'ip', in: 'query', required: false, description: 'IP 关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'username', in: 'query', required: false, description: '用户名关键字', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'success', in: 'query', required: false, description: '是否成功（true/false/1/0）', schema: new OA\Schema(type: 'string', enum: ['true', 'false', '1', '0'])),
            new OA\Parameter(name: 'startTime', in: 'query', required: false, description: '开始时间', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'endTime', in: 'query', required: false, description: '结束时间', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '查询成功', content: new OA\JsonContent(ref: '#/components/schemas/LoginLogListEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function list(ListLoginLogRequest $request)
    {
        return $this->ok($this->loginLogApplicationService->list($request->validated()));
    }

    #[Delete('')]
    #[ApiAuth(permission: 'system:login-log:delete')]
    #[OperationLog(module: '登录日志', action: '清理登录日志')]
    #[OA\Delete(
        path: '/api/admin/login-logs',
        operationId: 'adminLoginLogClear',
        description: '清理登录日志，不传 ids 时清空全部',
        tags: ['登录日志'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/ClearByIdsRequest')),
        responses: [
            new OA\Response(response: 200, description: '清理成功', content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')),
            new OA\Response(response: 401, description: '未登录或登录已失效', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 403, description: '权限不足', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
            new OA\Response(response: 422, description: '参数校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')),
        ]
    )]
    public function clear(ClearLoginLogRequest $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($request->validated()['ids'] ?? []))));

        return $this->ok($this->loginLogApplicationService->clear($ids));
    }
}
