<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Domain\Contracts;

use App\Models\AdminUser;

/**
 * 用户仓储接口。
 */
interface UserRepositoryInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listWithRoles(): array;

    public function findById(int $id): ?AdminUser;

    public function existsByUsername(string $username, ?int $excludeId = null): bool;

    public function create(array $payload): AdminUser;

    public function update(AdminUser $user, array $payload): AdminUser;

    public function delete(AdminUser $user): void;

    /**
     * @param array<int> $roleIds
     */
    public function syncRoles(AdminUser $user, array $roleIds): void;
}

