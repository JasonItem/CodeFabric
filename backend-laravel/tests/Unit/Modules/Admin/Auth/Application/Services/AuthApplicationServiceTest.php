<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Auth\Application\Services;

use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Models\Menu;
use App\Modules\Admin\Auth\Application\Services\AuthApplicationService;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use App\Modules\Admin\Auth\Domain\Services\AuthContextService;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use App\Support\JwtConfig;
use App\Support\JwtToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function login_returns_token_bundle_and_records_success_log(): void
    {
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        config()->set('jwt.ttl', 604800);

        $user = $this->makeUser(
            id: 7,
            username: 'admin',
            passwordHash: Hash::make('admin123'),
            nickname: '管理员',
            status: 'ACTIVE',
        );

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldReceive('findByUsername')->once()->with('admin')->andReturn($user);
        $authRepository->shouldReceive('getRoleIdsByUserId')->once()->with(7)->andReturn([1]);
        $authRepository->shouldReceive('getRolesByUserId')->once()->with(7)->andReturn([
            ['id' => 1, 'name' => '超级管理员', 'code' => 'super-admin'],
        ]);
        $authRepository->shouldReceive('getMenusByRoleIds')->once()->with([1])->andReturn(collect([
            $this->makeMenu(
                id: 11,
                name: '用户管理',
                type: 'MENU',
                permissionKey: null,
            ),
            $this->makeMenu(
                id: 12,
                name: '查询用户',
                type: 'BUTTON',
                permissionKey: 'system:user:list',
            ),
        ]));

        $loginLogRepository = Mockery::mock(LoginLogRepositoryInterface::class);
        $loginLogRepository->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (array $attributes): bool {
                $this->assertSame(7, $attributes['userId']);
                $this->assertSame('admin', $attributes['username']);
                $this->assertTrue($attributes['success']);
                $this->assertSame('登录成功', $attributes['message']);
                $this->assertSame('Chrome', $attributes['browser']);
                $this->assertSame('Windows', $attributes['os']);

                return true;
            }));

        $service = new AuthApplicationService(
            $authRepository,
            $loginLogRepository,
            new AuthContextService($authRepository),
            new JwtConfig(),
        );

        $result = $service->login('admin', 'admin123', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0) Chrome/123.0');
        $payload = JwtToken::decode($result['token'], config('jwt.secret'));

        $this->assertSame(604800, $result['ttl']);
        $this->assertSame(7, $payload['uid']);
        $this->assertSame('admin', $payload['username']);
        $this->assertSame('admin', $result['bundle']['user']['username']);
        $this->assertSame(['system:user:list'], $result['bundle']['permissions']);
        $this->assertCount(2, $result['bundle']['menus']);
    }

    #[Test]
    public function login_with_invalid_password_throws_unauthorized_and_records_failed_log(): void
    {
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        config()->set('jwt.ttl', 604800);

        $user = $this->makeUser(
            id: 7,
            username: 'admin',
            passwordHash: Hash::make('admin123'),
            nickname: '管理员',
            status: 'ACTIVE',
        );

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldReceive('findByUsername')->once()->with('admin')->andReturn($user);

        $loginLogRepository = Mockery::mock(LoginLogRepositoryInterface::class);
        $loginLogRepository->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (array $attributes): bool {
                $this->assertNull($attributes['userId']);
                $this->assertFalse($attributes['success']);
                $this->assertSame('用户名或密码错误', $attributes['message']);

                return true;
            }));

        $service = new AuthApplicationService(
            $authRepository,
            $loginLogRepository,
            new AuthContextService($authRepository),
            new JwtConfig(),
        );

        try {
            $service->login('admin', 'wrong-password', '127.0.0.1', 'Mozilla/5.0');
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::UNAUTHORIZED, $e->apiCode);
            $this->assertSame('用户名或密码错误', $e->getMessage());
        }
    }

    #[Test]
    public function change_password_updates_password_hash_when_old_password_matches(): void
    {
        $user = $this->makeUser(
            id: 8,
            username: 'ops',
            passwordHash: Hash::make('old-password'),
            nickname: '运营',
            status: 'ACTIVE',
        );

        $capturedHash = null;

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldReceive('updatePassword')
            ->once()
            ->with($user, Mockery::type('string'))
            ->andReturnUsing(function (AdminUser $target, string $hash) use (&$capturedHash): void {
                $capturedHash = $hash;
                $target->passwordHash = $hash;
            });

        $service = new AuthApplicationService(
            $authRepository,
            Mockery::mock(LoginLogRepositoryInterface::class),
            new AuthContextService($authRepository),
            new JwtConfig(),
        );

        $service->changePassword($user, 'old-password', 'new-password');

        $this->assertIsString($capturedHash);
        $this->assertNotSame('new-password', $capturedHash);
        $this->assertTrue(Hash::check('new-password', $capturedHash));
    }

    #[Test]
    public function change_password_with_wrong_old_password_throws_bad_request(): void
    {
        $user = $this->makeUser(
            id: 8,
            username: 'ops',
            passwordHash: Hash::make('old-password'),
            nickname: '运营',
            status: 'ACTIVE',
        );

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldNotReceive('updatePassword');

        $service = new AuthApplicationService(
            $authRepository,
            Mockery::mock(LoginLogRepositoryInterface::class),
            new AuthContextService($authRepository),
            new JwtConfig(),
        );

        try {
            $service->changePassword($user, 'bad-old-password', 'new-password');
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertSame('原密码错误', $e->getMessage());
        }
    }

    private function makeUser(
        int $id,
        string $username,
        string $passwordHash,
        string $nickname,
        string $status,
    ): AdminUser {
        $user = new AdminUser();
        $user->id = $id;
        $user->username = $username;
        $user->passwordHash = $passwordHash;
        $user->nickname = $nickname;
        $user->status = $status;

        return $user;
    }

    private function makeMenu(int $id, string $name, string $type, ?string $permissionKey): Menu
    {
        $menu = new Menu();
        $menu->id = $id;
        $menu->parentId = null;
        $menu->name = $name;
        $menu->path = '';
        $menu->component = null;
        $menu->icon = null;
        $menu->type = $type;
        $menu->permissionKey = $permissionKey;
        $menu->sort = 0;
        $menu->visible = true;

        return $menu;
    }
}
