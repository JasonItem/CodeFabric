<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Infrastructure\Persistence\Repositories;

use App\Models\Role;
use App\Modules\Admin\Role\Domain\Contracts\RoleRepositoryInterface;

final class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function listWithCountsAndMenus(): array
    {
        return Role::query()
            ->with(['menus:id', 'users:id'])
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Role $role) => [
                'id' => (int) $role->id,
                'name' => (string) $role->name,
                'code' => (string) $role->code,
                'description' => $role->description,
                'userCount' => $role->users->count(),
                'permissionCount' => $role->menus->count(),
                'menuIds' => $role->menus->pluck('id')->map(static fn ($id) => (int) $id)->values()->all(),
                'createdAt' => (string) $role->createdAt,
            ])
            ->values()
            ->all();
    }

    public function findById(int $id): ?Role
    {
        return Role::query()->find($id);
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool
    {
        return Role::query()
            ->where('code', $code)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function create(array $payload): Role
    {
        /** @var Role $role */
        $role = Role::query()->create($payload);

        return $role;
    }

    public function update(Role $role, array $payload): Role
    {
        $role->fill($payload);
        $role->save();

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->menus()->detach();
        $role->users()->detach();
        $role->delete();
    }

    public function syncMenus(Role $role, array $menuIds): void
    {
        $role->menus()->sync($menuIds);
    }
}

