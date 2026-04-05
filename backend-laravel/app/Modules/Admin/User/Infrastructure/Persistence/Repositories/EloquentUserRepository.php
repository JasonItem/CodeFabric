<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Infrastructure\Persistence\Repositories;

use App\Models\AdminUser;
use App\Modules\Admin\User\Domain\Contracts\UserRepositoryInterface;

/**
 * Eloquent 用户仓储实现。
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    public function listWithRoles(): array
    {
        return AdminUser::query()
            ->with('roles')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (AdminUser $user) => [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'nickname' => (string) ($user->nickname ?? ''),
                'status' => (string) $user->status,
                'createdAt' => (string) $user->createdAt,
                'roles' => $user->roles->map(static fn ($role) => [
                    'id' => (int) $role->id,
                    'name' => (string) $role->name,
                    'code' => (string) $role->code,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function findById(int $id): ?AdminUser
    {
        return AdminUser::query()->find($id);
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        return AdminUser::query()
            ->where('username', $username)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function create(array $payload): AdminUser
    {
        /** @var AdminUser $user */
        $user = AdminUser::query()->create($payload);

        return $user;
    }

    public function update(AdminUser $user, array $payload): AdminUser
    {
        $user->fill($payload);
        $user->save();

        return $user;
    }

    public function delete(AdminUser $user): void
    {
        $user->roles()->detach();
        $user->delete();
    }

    public function syncRoles(AdminUser $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }
}

