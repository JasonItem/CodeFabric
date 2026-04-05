<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Application\Services;

use App\Attributes\WithRedisLock;
use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use App\Modules\Admin\Auth\Domain\Services\AuthContextService;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;
use App\Support\JwtConfig;
use App\Support\JwtToken;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * 认证应用服务。
 *
 * 约束：
 * - Controller 只负责 HTTP 协议层逻辑；
 * - 所有认证业务流程在本服务编排；
 * - 持久化通过 Repository 接口完成。
 */
final class AuthApplicationService extends ApplicationService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly LoginLogRepositoryInterface $loginLogRepository,
        private readonly AuthContextService $authContextService,
        private readonly JwtConfig $jwtConfig,
    ) {
    }

    /**
     * 登录。
     *
     * @return array{token:string,ttl:int,bundle:array<string,mixed>}
     */
    #[WithTransaction]
    public function login(string $username, string $password, ?string $ip, ?string $userAgent): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($username, $password, $ip, $userAgent): array {
            $user = $this->authRepository->findByUsername($username);

            $ok = $user instanceof AdminUser
                && $this->verifyPassword($password, (string) $user->passwordHash)
                && $user->status === 'ACTIVE';

            $this->recordLogin(
                user: $ok ? $user : null,
                username: $username,
                ip: $ip,
                userAgent: $userAgent,
                success: $ok,
                message: $ok ? '登录成功' : '用户名或密码错误'
            );

            if (!$ok || !$user) {
                throw new ApiBusinessException(ApiCode::UNAUTHORIZED, '用户名或密码错误');
            }

            $ttl = $this->jwtConfig->ttl();
            $token = JwtToken::encode([
                'uid' => (int) $user->id,
                'username' => (string) $user->username,
                'iat' => time(),
                'exp' => time() + $ttl,
            ], $this->jwtConfig->secret());

            return [
                'token' => $token,
                'ttl' => $ttl,
                'bundle' => $this->authContextService->buildBundle($user),
            ];
        });
    }

    /**
     * 获取登录上下文。
     *
     * @return array<string,mixed>
     */
    public function me(AdminUser $user): array
    {
        return $this->authContextService->buildBundle($user);
    }

    /**
     * 退出登录（记录日志）。
     */
    public function logout(?AdminUser $user, ?string $ip, ?string $userAgent): void
    {
        if (!$user) {
            return;
        }

        $this->recordLogin(
            user: $user,
            username: (string) $user->username,
            ip: $ip,
            userAgent: $userAgent,
            success: true,
            message: '退出成功'
        );
    }

    /**
     * 修改密码。
     */
    #[WithTransaction]
    #[WithRedisLock(key: 'lock:change-password:{uid}', seconds: 5)]
    public function changePassword(AdminUser $user, string $oldPassword, string $newPassword): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($user, $oldPassword, $newPassword): void {
            if (!$this->verifyPassword($oldPassword, (string) $user->passwordHash)) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '原密码错误');
            }

            $passwordHash = Hash::make($newPassword);
            $this->authRepository->updatePassword($user, $passwordHash);
        });
    }

    /**
     * 兼容 Node bcryptjs 生成的 `$2a/$2b` 历史哈希。
     */
    private function verifyPassword(string $plain, string $hash): bool
    {
        try {
            return Hash::check($plain, $hash);
        } catch (RuntimeException) {
            return password_verify($plain, $hash);
        }
    }

    private function recordLogin(
        ?AdminUser $user,
        string $username,
        ?string $ip,
        ?string $userAgent,
        bool $success,
        string $message,
    ): void {
        $ua = (string) ($userAgent ?? '');

        $this->loginLogRepository->record([
            'userId' => $user?->id,
            'username' => $username,
            'client' => null,
            'device' => null,
            'browser' => $this->parseBrowser($ua),
            'os' => $this->parseOs($ua),
            'ip' => $ip,
            'location' => '未知地点',
            'userAgent' => $ua,
            'success' => $success,
            'message' => $message,
            'createdAt' => now(),
        ]);
    }

    private function parseBrowser(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Edg') => 'Edge',
            default => null,
        };
    }

    private function parseOs(string $ua): ?string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            default => null,
        };
    }
}
