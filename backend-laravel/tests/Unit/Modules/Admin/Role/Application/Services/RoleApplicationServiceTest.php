<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Role\Application\Services;

use App\Enums\ApiCode;
use App\Models\Role;
use App\Modules\Admin\Role\Application\Services\RoleApplicationService;
use App\Modules\Admin\Role\Domain\Contracts\RoleRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoleApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function create_rejects_duplicate_role_code(): void
    {
        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('existsByCode')->once()->with('admin')->andReturn(true);
        $repository->shouldNotReceive('create');

        $service = new RoleApplicationService($repository);

        try {
            $service->create([
                'name' => '管理员',
                'code' => 'admin',
                'description' => 'desc',
            ]);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::CONFLICT, $e->apiCode);
            $this->assertSame('角色编码已存在', $e->getMessage());
        }
    }

    #[Test]
    public function create_returns_mapped_role_after_repository_create(): void
    {
        $role = $this->makeRole(5, '管理员', 'admin');

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('existsByCode')->once()->with('admin')->andReturn(false);
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'name' => '管理员',
                'code' => 'admin',
                'description' => '系统管理员',
            ])
            ->andReturn($role);
        $repository->shouldReceive('listWithCountsAndMenus')->once()->andReturn([
            [
                'id' => 5,
                'name' => '管理员',
                'code' => 'admin',
                'description' => '系统管理员',
                'userCount' => 2,
                'menus' => [],
            ],
        ]);

        $service = new RoleApplicationService($repository);

        $result = $service->create([
            'name' => '管理员',
            'code' => 'admin',
            'description' => '系统管理员',
        ]);

        $this->assertSame(5, $result['id']);
        $this->assertSame('admin', $result['code']);
    }

    #[Test]
    public function update_throws_not_found_when_role_is_missing(): void
    {
        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(99)->andReturn(null);
        $repository->shouldNotReceive('update');

        $service = new RoleApplicationService($repository);

        try {
            $service->update(99, ['name' => 'missing']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('角色不存在', $e->getMessage());
        }
    }

    #[Test]
    public function assign_permissions_syncs_unique_menu_ids(): void
    {
        $role = $this->makeRole(7, '运营', 'ops');

        $repository = Mockery::mock(RoleRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(7)->andReturn($role);
        $repository->shouldReceive('syncMenus')->once()->with($role, [1, 2, 3]);

        $service = new RoleApplicationService($repository);
        $service->assignPermissions(7, [1, '2', 1, 3]);

        $this->assertTrue(true);
    }

    private function makeRole(int $id, string $name, string $code): Role
    {
        $role = new Role();
        $role->id = $id;
        $role->name = $name;
        $role->code = $code;

        return $role;
    }
}
