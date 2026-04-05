<?php

declare(strict_types=1);

namespace App\Modules\Admin\Role\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Modules\Admin\Role\Domain\Contracts\RoleRepositoryInterface;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;

final class RoleApplicationService extends ApplicationService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        return $this->roleRepository->listWithCountsAndMenus();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function create(array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($payload) {
            if ($this->roleRepository->existsByCode((string) $payload['code'])) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '角色编码已存在');
            }

            $role = $this->roleRepository->create([
                'name' => (string) $payload['name'],
                'code' => (string) $payload['code'],
                'description' => $payload['description'] ?? null,
            ]);

            return $this->mapOne((int) $role->id);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function update(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload) {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '角色不存在');
            }

            if (isset($payload['code']) && $this->roleRepository->existsByCode((string) $payload['code'], $id)) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '角色编码已存在');
            }

            $updateData = [];
            foreach (['name', 'code', 'description'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $updateData[$field] = $payload[$field];
                }
            }

            $this->roleRepository->update($role, $updateData);

            return $this->mapOne($id);
        });
    }

    #[WithTransaction]
    public function delete(int $id): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id): void {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '角色不存在');
            }

            $this->roleRepository->delete($role);
        });
    }

    /**
     * @param array<int> $menuIds
     */
    #[WithTransaction]
    public function assignPermissions(int $id, array $menuIds): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id, $menuIds): void {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '角色不存在');
            }
            $this->roleRepository->syncMenus($role, array_values(array_unique(array_map('intval', $menuIds))));
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function mapOne(int $id): array
    {
        foreach ($this->roleRepository->listWithCountsAndMenus() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        throw new ApiBusinessException(ApiCode::NOT_FOUND, '角色不存在');
    }
}
