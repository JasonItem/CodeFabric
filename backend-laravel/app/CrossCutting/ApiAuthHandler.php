<?php

declare(strict_types=1);

namespace App\CrossCutting;

use App\Attributes\ApiAuth;
use App\Enums\ApiCode;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use App\Modules\Admin\Auth\Domain\Services\AuthContextService;
use App\Support\ApiResponse;
use App\Support\JwtConfig;
use App\Support\JwtToken;
use App\Support\ReflectRoute;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 注解鉴权中间件：支持登录开关与严格鉴权开关。
 */
final class ApiAuthHandler
{
    public function __construct(
        private readonly AuthContextService $authContextService,
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JwtConfig $jwtConfig,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $method = ReflectRoute::method();
        $attrs = $method?->getAttributes(ApiAuth::class) ?? [];

        if (empty($attrs)) {
            return $next($request);
        }

        /** @var ApiAuth $authMeta */
        $authMeta = $attrs[0]->newInstance();

        if (!filter_var((string) env('API_AUTH_ENABLED', 'true'), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        if (!$authMeta->loginRequired) {
            return $next($request);
        }

        $token = (string) ($request->cookie('admin_token') ?: $this->extractBearerToken($request));
        if ($token === '') {
            return ApiResponse::error('未登录或登录已失效', ApiCode::UNAUTHORIZED);
        }

        $payload = JwtToken::decode($token, $this->jwtConfig->secret());
        if (!$payload || empty($payload['uid'])) {
            return ApiResponse::error('登录凭证无效，请重新登录', ApiCode::UNAUTHORIZED);
        }

        $user = $this->authRepository->findById((int) $payload['uid']);
        if (!$user || $user->status !== 'ACTIVE') {
            return ApiResponse::error('账号不可用或已被禁用', ApiCode::UNAUTHORIZED);
        }

        $request->attributes->set('adminUser', $user);

        $strict = filter_var((string) env('API_STRICT_PERMISSION_ENABLED', 'true'), FILTER_VALIDATE_BOOL);
        if (!$strict || !$authMeta->permission) {
            return $next($request);
        }

        if (!$this->authContextService->hasPermission($user, $authMeta->permission)) {
            return ApiResponse::error('没有权限执行该操作', ApiCode::FORBIDDEN);
        }

        return $next($request);
    }

    private function extractBearerToken(Request $request): string
    {
        $auth = (string) $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }

        return '';
    }
}
