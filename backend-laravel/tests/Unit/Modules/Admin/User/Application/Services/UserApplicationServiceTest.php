<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\User\Application\Services;

use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Modules\Admin\User\Application\Services\UserApplicationService;
use App\Modules\Admin\User\Domain\Contracts\UserRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function create_rejects_duplicate_username(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('existsByUsername')->once()->with('admin')->andReturn(true);
        $repository->shouldNotReceive('create');
        $repository->shouldNotReceive('syncRoles');

        $service = new UserApplicationService($repository);

        try {
            $service->create([
                'username' => 'admin',
                'nickname' => '管理员',
                'password' => 'admin123',
                'status' => 'ACTIVE',
                'roleIds' => [1, 2],
            ]);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::CONFLICT, $e->apiCode);
            $this->assertSame('账号已存在', $e->getMessage());
        }
    }

    #[Test]
    public function create_hashes_password_and_syncs_unique_role_ids(): void
    {
        $user = $this->makeUser(10, 'editor');

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('existsByUsername')->once()->with('editor')->andReturn(false);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $this->assertSame('editor', $payload['username']);
                $this->assertSame('编辑', $payload['nickname']);
                $this->assertSame('ACTIVE', $payload['status']);
                $this->assertIsString($payload['passwordHash']);
                $this->assertNotSame('editor123', $payload['passwordHash']);
                $this->assertTrue(Hash::check('editor123', $payload['passwordHash']));

                return true;
            }))
            ->andReturn($user);
        $repository->shouldReceive('syncRoles')->once()->with($user, [2, 3]);
        $repository->shouldReceive('listWithRoles')->once()->andReturn([
            [
                'id' => 10,
                'username' => 'editor',
                'nickname' => '编辑',
                'status' => 'ACTIVE',
                'roles' => [
                    ['id' => 2, 'name' => '运营管理员'],
                    ['id' => 3, 'name' => '内容编辑'],
                ],
            ],
        ]);

        $service = new UserApplicationService($repository);

        $result = $service->create([
            'username' => 'editor',
            'nickname' => '编辑',
            'password' => 'editor123',
            'status' => 'ACTIVE',
            'roleIds' => [2, '3', 2],
        ]);

        $this->assertSame(10, $result['id']);
        $this->assertSame('editor', $result['username']);
    }

    #[Test]
    public function update_rejects_duplicate_username_for_other_user(): void
    {
        $user = $this->makeUser(10, 'editor');

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(10)->andReturn($user);
        $repository->shouldReceive('existsByUsername')->once()->with('taken', 10)->andReturn(true);
        $repository->shouldNotReceive('update');

        $service = new UserApplicationService($repository);

        try {
            $service->update(10, ['username' => 'taken']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::CONFLICT, $e->apiCode);
            $this->assertSame('账号已存在', $e->getMessage());
        }
    }

    #[Test]
    public function update_hashes_password_and_syncs_roles_when_provided(): void
    {
        $user = $this->makeUser(11, 'ops');

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(11)->andReturn($user);
        $repository->shouldReceive('existsByUsername')->once()->with('ops-updated', 11)->andReturn(false);
        $repository->shouldReceive('update')
            ->once()
            ->with($user, Mockery::on(function (array $payload): bool {
                $this->assertSame('ops-updated', $payload['username']);
                $this->assertSame('运营人员', $payload['nickname']);
                $this->assertSame('DISABLED', $payload['status']);
                $this->assertTrue(Hash::check('new-secret', $payload['passwordHash']));

                return true;
            }))
            ->andReturn($user);
        $repository->shouldReceive('syncRoles')->once()->with($user, [3, 4]);
        $repository->shouldReceive('listWithRoles')->once()->andReturn([
            [
                'id' => 11,
                'username' => 'ops-updated',
                'nickname' => '运营人员',
                'status' => 'DISABLED',
                'roles' => [
                    ['id' => 3, 'name' => '审计员'],
                    ['id' => 4, 'name' => '运营管理员'],
                ],
            ],
        ]);

        $service = new UserApplicationService($repository);

        $result = $service->update(11, [
            'username' => 'ops-updated',
            'nickname' => '运营人员',
            'status' => 'DISABLED',
            'password' => 'new-secret',
            'roleIds' => ['3', 4, 3],
        ]);

        $this->assertSame(11, $result['id']);
        $this->assertSame('ops-updated', $result['username']);
    }

    #[Test]
    public function delete_throws_not_found_when_user_does_not_exist(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(999)->andReturn(null);
        $repository->shouldNotReceive('delete');

        $service = new UserApplicationService($repository);

        try {
            $service->delete(999);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('用户不存在', $e->getMessage());
        }
    }

    private function makeUser(int $id, string $username): AdminUser
    {
        $user = new AdminUser();
        $user->id = $id;
        $user->username = $username;
        $user->nickname = $username;
        $user->status = 'ACTIVE';

        return $user;
    }
}
