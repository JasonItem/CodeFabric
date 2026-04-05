<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Modules\Admin\Dictionary\Domain\Contracts\DictionaryRepositoryInterface;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;

/**
 * 字典应用服务：负责字典类型和字典项业务编排。
 */
final class DictionaryApplicationService extends ApplicationService
{
    public function __construct(
        private readonly DictionaryRepositoryInterface $dictionaryRepository,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listTypes(?string $keyword = null): array
    {
        return $this->dictionaryRepository->listTypes($keyword);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function createType(array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($payload): array {
            if ($this->dictionaryRepository->existsTypeCode((string) $payload['code'])) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '字典类型编码已存在');
            }

            $type = $this->dictionaryRepository->createType($payload);

            return $this->mapType((int) $type->id);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function updateType(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload): array {
            $type = $this->dictionaryRepository->findTypeById($id);
            if (!$type) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典类型不存在');
            }

            if (array_key_exists('code', $payload) && $this->dictionaryRepository->existsTypeCode((string) $payload['code'], $id)) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '字典类型编码已存在');
            }

            $this->dictionaryRepository->updateType($type, $payload);

            return $this->mapType($id);
        });
    }

    #[WithTransaction]
    public function deleteType(int $id): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id): void {
            $type = $this->dictionaryRepository->findTypeById($id);
            if (!$type) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典类型不存在');
            }

            $this->dictionaryRepository->deleteType($type);
        });
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listItems(int $typeId, ?string $keyword = null): array
    {
        $type = $this->dictionaryRepository->findTypeById($typeId);
        if (!$type) {
            throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典类型不存在');
        }

        return $this->dictionaryRepository->listItems($typeId, $keyword);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function createItem(int $typeId, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($typeId, $payload): array {
            $type = $this->dictionaryRepository->findTypeById($typeId);
            if (!$type) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典类型不存在');
            }

            $item = $this->dictionaryRepository->createItem($payload + ['dictTypeId' => $typeId]);

            return $this->mapItem((int) $item->id);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function updateItem(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload): array {
            $item = $this->dictionaryRepository->findItemById($id);
            if (!$item) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典项不存在');
            }

            $this->dictionaryRepository->updateItem($item, $payload);

            return $this->mapItem($id);
        });
    }

    #[WithTransaction]
    public function deleteItem(int $id): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id): void {
            $item = $this->dictionaryRepository->findItemById($id);
            if (!$item) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典项不存在');
            }

            $this->dictionaryRepository->deleteItem($item);
        });
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function optionsByCode(string $code): array
    {
        return $this->dictionaryRepository->listOptionsByCode($code);
    }

    /**
     * @return array<string,mixed>
     */
    private function mapType(int $id): array
    {
        foreach ($this->dictionaryRepository->listTypes() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典类型不存在');
    }

    /**
     * @return array<string,mixed>
     */
    private function mapItem(int $id): array
    {
        foreach ($this->dictionaryRepository->listTypes() as $type) {
            foreach ($this->dictionaryRepository->listItems((int) $type['id']) as $row) {
                if ((int) $row['id'] === $id) {
                    return $row;
                }
            }
        }

        throw new ApiBusinessException(ApiCode::NOT_FOUND, '字典项不存在');
    }
}
