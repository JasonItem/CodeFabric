<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Models\AdminUser;
use App\Modules\Admin\Auth\Application\Services\AuthApplicationService;
use App\Modules\Admin\Auth\Http\Requests\ChangePasswordRequest;
use App\Modules\Admin\Auth\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 认证模块 HTTP 入口。
 *
 * 设计约束：
 * - 控制器仅做协议层职责（入参、Cookie、响应）；
 * - 业务规则编排全部下沉到 Application Service；
 * - 禁止在控制器中直接读写数据库。
 */
#[Prefix('auth')]
#[OA\Tag(name: '认证管理', description: '登录、登出、登录态获取与密码修改')]
final class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthApplicationService $authApplicationService,
    ) {
    }

    /**
     * 登录。
     */
    #[Post('login')]
    #[Middleware('throttle:admin-login')]
    #[ApiAuth(loginRequired: false)]
    #[OperationLog(module: '认证管理', action: '登录')]
    #[OA\Post(
        path: '/api/admin/auth/login',
        operationId: 'adminAuthLogin',
        description: '管理员登录，成功后写入 admin_token Cookie',
        tags: ['认证管理'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthLoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '登录成功',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthSuccessEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '用户名或密码错误',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 422,
                description: '参数校验失败',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function login(LoginRequest $request)
    {
        $result = $this->authApplicationService->login(
            username: (string) $request->string('username'),
            password: (string) $request->string('password'),
            ip: $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        $ttlMinutes = (int) floor(((int) $result['ttl']) / 60);
        $path = (string) config('session.path', '/');
        $domain = config('session.domain');
        $secure = app()->environment('production') ? true : (bool) config('session.secure', false);

        return $this->ok($result['bundle'])
            ->cookie('admin_token', (string) $result['token'], $ttlMinutes, $path, $domain, $secure, true, false, 'Lax')
            ->cookie((string) env('AUTH_TOKEN_KEY', 'admin_access_token'), 'logged-in', $ttlMinutes, $path, $domain, $secure, false, false, 'Lax');
    }

    /**
     * 获取当前登录信息。
     */
    #[Get('me')]
    #[ApiAuth]
    #[OA\Get(
        path: '/api/admin/auth/me',
        operationId: 'adminAuthMe',
        description: '获取当前登录用户及权限包',
        tags: ['认证管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '获取成功',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthSuccessEnvelope')
            ),
            new OA\Response(
                response: 401,
                description: '未登录',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function me(Request $request)
    {
        /** @var AdminUser $user */
        $user = $request->attributes->get('adminUser');

        return $this->ok($this->authApplicationService->me($user));
    }

    /**
     * 退出登录。
     */
    #[Post('logout')]
    #[ApiAuth]
    #[OperationLog(module: '认证管理', action: '退出登录')]
    #[OA\Post(
        path: '/api/admin/auth/logout',
        operationId: 'adminAuthLogout',
        description: '退出登录并清理鉴权 Cookie',
        tags: ['认证管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '退出成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')
            ),
        ]
    )]
    public function logout(Request $request)
    {
        /** @var AdminUser|null $user */
        $user = $request->attributes->get('adminUser');
        $this->authApplicationService->logout($user, $request->ip(), (string) $request->userAgent());

        $path = (string) config('session.path', '/');
        $domain = config('session.domain');

        return $this->ok(true)
            ->withCookie(cookie()->forget('admin_token', $path, $domain))
            ->withCookie(cookie()->forget((string) env('AUTH_TOKEN_KEY', 'admin_access_token'), $path, $domain));
    }

    /**
     * 修改密码。
     */
    #[Post('change-password')]
    #[ApiAuth(permission: 'system:auth:change-password')]
    #[OperationLog(module: '认证管理', action: '修改密码')]
    #[OA\Post(
        path: '/api/admin/auth/change-password',
        operationId: 'adminAuthChangePassword',
        description: '修改当前用户密码',
        tags: ['认证管理'],
        security: [['cookieAuth' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthChangePasswordRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '修改成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiEnvelope')
            ),
            new OA\Response(
                response: 400,
                description: '原密码不正确',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
            new OA\Response(
                response: 403,
                description: '权限不足',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorEnvelope')
            ),
        ]
    )]
    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var AdminUser $user */
        $user = $request->attributes->get('adminUser');

        $this->authApplicationService->changePassword(
            user: $user,
            oldPassword: (string) $request->string('oldPassword'),
            newPassword: (string) $request->string('newPassword'),
        );

        return $this->ok(true, '修改成功');
    }
}
