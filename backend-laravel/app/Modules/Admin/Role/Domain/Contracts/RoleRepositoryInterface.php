<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Domain\Contracts;

use App\Models\Role;

interface RoleRepositoryInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listWithCountsAndMenus(): array;

    public function findById(int $id): ?Role;

    public function existsByCode(string $code, ?int $excludeId = null): bool;

    public function create(array $payload): Role;

    public function update(Role $role, array $payload): Role;

    public function delete(Role $role): void;

    /**
     * @param array<int> $menuIds
     */
    public function syncMenus(Role $role, array $menuIds): void;
}

