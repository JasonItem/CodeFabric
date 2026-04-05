<?php

declare(strict_types=1);

namespace App\Modules\Admin\Auth\Domain\Contracts;

use App\Models\AdminUser;
use Illuminate\Support\Collection;

/**
 * 认证仓储接口。
 *
 * 仅定义领域行为，不暴露 ORM 细节给应用层。
 */
interface AuthRepositoryInterface
{
    public function findById(int $userId): ?AdminUser;

    public function findByUsername(string $username): ?AdminUser;

    public function updatePassword(AdminUser $user, string $passwordHash): void;

    /**
     * @return array<int>
     */
    public function getRoleIdsByUserId(int $userId): array;

    /**
     * @return array<int,array{id:int,name:string,code:string}>
     */
    public function getRolesByUserId(int $userId): array;

    /**
     * @param array<int> $roleIds
     * @return Collection<int,\App\Models\Menu>
     */
    public function getMenusByRoleIds(array $roleIds): Collection;

    /**
     * @param array<int> $roleIds
     */
    public function hasPermission(array $roleIds, string $permission): bool;
}

