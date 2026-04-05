<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Infrastructure\Persistence\Repositories;

use App\Models\AdminUser;
use App\Models\Menu;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * 基于 Eloquent 的认证仓储实现。
 */
final class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function findById(int $userId): ?AdminUser
    {
        return AdminUser::query()->find($userId);
    }

    public function findByUsername(string $username): ?AdminUser
    {
        return AdminUser::query()->where('username', $username)->first();
    }

    public function updatePassword(AdminUser $user, string $passwordHash): void
    {
        $user->passwordHash = $passwordHash;
        $user->save();
    }

    public function getRoleIdsByUserId(int $userId): array
    {
        $user = AdminUser::query()->find($userId);
        if (!$user) {
            return [];
        }

        /** @var array<int> $roleIds */
        $roleIds = $user->roles()
            ->pluck('Role.id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return $roleIds;
    }

    public function getRolesByUserId(int $userId): array
    {
        $user = AdminUser::query()->find($userId);
        if (!$user) {
            return [];
        }

        return $user->roles()
            ->get(['Role.id', 'Role.name', 'Role.code'])
            ->map(static fn ($role) => [
                'id' => (int) $role->id,
                'name' => (string) $role->name,
                'code' => (string) $role->code,
            ])
            ->values()
            ->all();
    }

    public function getMenusByRoleIds(array $roleIds): Collection
    {
        return Menu::query()
            ->when(!empty($roleIds), function ($query) use ($roleIds) {
                $query->whereIn('id', function ($sub) use ($roleIds) {
                    $sub->select('menuId')->from('RoleMenu')->whereIn('roleId', $roleIds);
                });
            })
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function hasPermission(array $roleIds, string $permission): bool
    {
        if ($permission === '' || empty($roleIds)) {
            return false;
        }

        return Menu::query()
            ->where('permissionKey', $permission)
            ->whereIn('id', function ($sub) use ($roleIds) {
                $sub->select('menuId')->from('RoleMenu')->whereIn('roleId', $roleIds);
            })
            ->exists();
    }
}
