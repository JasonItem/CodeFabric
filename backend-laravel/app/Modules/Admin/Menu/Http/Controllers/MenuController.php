<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Shared\Http\ApiController;
use App\Modules\Admin\Menu\Application\Services\MenuApplicationService;
use App\Modules\Admin\Menu\Http\Requests\CreateMenuRequest;
use App\Modules\Admin\Menu\Http\Requests\UpdateMenuRequest;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('menus')]
final class MenuController extends ApiController
{
    public function __construct(
        private readonly MenuApplicationService $menuApplicationService,
    ) {
    }

    #[Get('')]
    #[ApiAuth(permission: 'system:menu:list')]
    public function list()
    {
        return $this->ok($this->menuApplicationService->list());
    }

    #[Post('')]
    #[ApiAuth(permission: 'system:menu:add')]
    #[OperationLog(module: '菜单管理', action: '新增菜单')]
    public function create(CreateMenuRequest $request)
    {
        return $this->ok($this->menuApplicationService->create($request->validated()));
    }

    #[Put('{id}')]
    #[ApiAuth(permission: 'system:menu:edit')]
    #[OperationLog(module: '菜单管理', action: '编辑菜单')]
    public function update(int $id, UpdateMenuRequest $request)
    {
        return $this->ok($this->menuApplicationService->update($id, $request->validated()));
    }

    #[Delete('{id}')]
    #[ApiAuth(permission: 'system:menu:delete')]
    #[OperationLog(module: '菜单管理', action: '删除菜单')]
    public function delete(int $id)
    {
        $this->menuApplicationService->delete($id);

        return $this->ok(true);
    }
}
