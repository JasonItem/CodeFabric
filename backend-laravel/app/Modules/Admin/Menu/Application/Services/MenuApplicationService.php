<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Modules\Admin\Menu\Domain\Contracts\MenuRepositoryInterface;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;
use Illuminate\Support\Facades\DB;

final class MenuApplicationService extends ApplicationService
{
    public function __construct(
        private readonly MenuRepositoryInterface $menuRepository,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        return $this->menuRepository->list();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function create(array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($payload): array {
            $menu = $this->menuRepository->create($this->normalizePayload($payload));

            return $this->mapOne((int) $menu->id);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function update(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload): array {
            $menu = $this->menuRepository->findById($id);
            if (!$menu) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '菜单不存在');
            }

            $update = $this->normalizePayload($payload, true);
            if (!empty($update)) {
                $this->menuRepository->update($menu, $update);
            }

            return $this->mapOne($id);
        });
    }

    #[WithTransaction]
    public function delete(int $id): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id): void {
            $menu = $this->menuRepository->findById($id);
            if (!$menu) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '菜单不存在');
            }

            $childrenExists = DB::table('Menu')->where('parentId', $id)->exists();
            if ($childrenExists) {
                throw new ApiBusinessException(ApiCode::BAD_REQUEST, '请先删除子分组');
            }

            DB::table('RoleMenu')->where('menuId', $id)->delete();
            $this->menuRepository->delete($menu);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizePayload(array $payload, bool $partial = false): array
    {
        $fields = ['parentId', 'name', 'path', 'component', 'icon', 'type', 'permissionKey', 'sort', 'visible'];
        $data = [];
        foreach ($fields as $field) {
            if ($partial && !array_key_exists($field, $payload)) {
                continue;
            }
            if (!$partial && !array_key_exists($field, $payload)) {
                continue;
            }
            $data[$field] = $payload[$field];
        }

        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    private function mapOne(int $id): array
    {
        foreach ($this->menuRepository->list() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        throw new ApiBusinessException(ApiCode::NOT_FOUND, '菜单不存在');
    }
}
