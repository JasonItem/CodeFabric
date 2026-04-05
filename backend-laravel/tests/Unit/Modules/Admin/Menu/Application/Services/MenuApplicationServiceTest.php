<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Menu\Application\Services;

use App\Enums\ApiCode;
use App\Models\Menu;
use App\Modules\Admin\Menu\Application\Services\MenuApplicationService;
use App\Modules\Admin\Menu\Domain\Contracts\MenuRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MenuApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = (string) config('database.default');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->dropIfExists('RoleMenu');
        Schema::connection('sqlite')->dropIfExists('Menu');

        Schema::connection('sqlite')->create('Menu', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('parentId')->nullable();
            $table->string('name');
        });

        Schema::connection('sqlite')->create('RoleMenu', function (Blueprint $table): void {
            $table->integer('roleId');
            $table->integer('menuId');
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('sqlite')->dropIfExists('RoleMenu');
        Schema::connection('sqlite')->dropIfExists('Menu');

        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalDefaultConnection);
        DB::purge($this->originalDefaultConnection);
        DB::reconnect($this->originalDefaultConnection);

        parent::tearDown();
    }

    #[Test]
    public function create_normalizes_payload_and_returns_created_menu(): void
    {
        $menu = $this->makeMenu(10, '用户管理');

        $repository = Mockery::mock(MenuRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'parentId' => null,
                'name' => '用户管理',
                'path' => '/users',
                'component' => 'rbac/users',
                'icon' => 'users',
                'type' => 'MENU',
                'permissionKey' => null,
                'sort' => 10,
                'visible' => true,
            ])
            ->andReturn($menu);
        $repository->shouldReceive('list')->once()->andReturn([
            [
                'id' => 10,
                'name' => '用户管理',
                'path' => '/users',
                'type' => 'MENU',
            ],
        ]);

        $service = new MenuApplicationService($repository);

        $result = $service->create([
            'parentId' => null,
            'name' => '用户管理',
            'path' => '/users',
            'component' => 'rbac/users',
            'icon' => 'users',
            'type' => 'MENU',
            'permissionKey' => null,
            'sort' => 10,
            'visible' => true,
            'ignoredField' => 'ignored',
        ]);

        $this->assertSame(10, $result['id']);
    }

    #[Test]
    public function update_throws_not_found_when_menu_does_not_exist(): void
    {
        $repository = Mockery::mock(MenuRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(404)->andReturn(null);
        $repository->shouldNotReceive('update');

        $service = new MenuApplicationService($repository);

        try {
            $service->update(404, ['name' => 'missing']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('菜单不存在', $e->getMessage());
        }
    }

    #[Test]
    public function delete_rejects_menu_when_children_exist(): void
    {
        DB::table('Menu')->insert([
            ['id' => 20, 'parentId' => null, 'name' => '系统管理'],
            ['id' => 21, 'parentId' => 20, 'name' => '子菜单'],
        ]);

        $menu = $this->makeMenu(20, '系统管理');

        $repository = Mockery::mock(MenuRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(20)->andReturn($menu);
        $repository->shouldNotReceive('delete');

        $service = new MenuApplicationService($repository);

        try {
            $service->delete(20);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::BAD_REQUEST, $e->apiCode);
            $this->assertSame('请先删除子分组', $e->getMessage());
        }
    }

    #[Test]
    public function delete_removes_role_menu_links_before_deleting_menu(): void
    {
        DB::table('Menu')->insert([
            ['id' => 30, 'parentId' => null, 'name' => '日志中心'],
        ]);
        DB::table('RoleMenu')->insert([
            ['roleId' => 1, 'menuId' => 30],
            ['roleId' => 2, 'menuId' => 30],
        ]);

        $menu = $this->makeMenu(30, '日志中心');

        $repository = Mockery::mock(MenuRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(30)->andReturn($menu);
        $repository->shouldReceive('delete')->once()->with($menu);

        $service = new MenuApplicationService($repository);
        $service->delete(30);

        $this->assertSame(0, DB::table('RoleMenu')->where('menuId', 30)->count());
    }

    private function makeMenu(int $id, string $name): Menu
    {
        $menu = new Menu();
        $menu->id = $id;
        $menu->name = $name;

        return $menu;
    }
}
