<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Domain\Services;

use App\Models\AdminUser;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;

/**
 * 认证上下文领域服务。
 *
 * 负责聚合用户、角色、菜单和权限数据，避免控制器和中间件直接拼接业务结构。
 */
final class AuthContextService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    /**
     * @return array{user: array<string,mixed>, roles: array<int,array<string,mixed>>, menus: array<int,array<string,mixed>>, permissions: array<int,string>}
     */
    public function buildBundle(AdminUser $user): array
    {
        $roleIds = $this->authRepository->getRoleIdsByUserId((int) $user->id);
        $roles = $this->authRepository->getRolesByUserId((int) $user->id);
        $menus = $this->authRepository->getMenusByRoleIds($roleIds);

        $permissions = $menus
            ->pluck('permissionKey')
            ->filter(static fn ($key) => is_string($key) && $key !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'user' => [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'nickname' => (string) ($user->nickname ?? ''),
            ],
            'roles' => $roles,
            'menus' => $menus->map(static fn ($menu) => [
                'id' => (int) $menu->id,
                'parentId' => $menu->parentId !== null ? (int) $menu->parentId : null,
                'name' => (string) $menu->name,
                'path' => (string) ($menu->path ?? ''),
                'component' => $menu->component,
                'icon' => $menu->icon,
                'type' => (string) $menu->type,
                'permissionKey' => $menu->permissionKey,
                'sort' => (int) $menu->sort,
                'visible' => (bool) $menu->visible,
            ])->values()->all(),
            'permissions' => $permissions,
        ];
    }

    public function hasPermission(AdminUser $user, string $permission): bool
    {
        if ($permission === '') {
            return true;
        }

        $roleIds = $this->authRepository->getRoleIdsByUserId((int) $user->id);

        return $this->authRepository->hasPermission($roleIds, $permission);
    }
}

