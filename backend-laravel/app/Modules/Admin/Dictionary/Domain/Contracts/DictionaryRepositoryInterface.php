<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Domain\Contracts;

use App\Models\DictItem;
use App\Models\DictType;

interface DictionaryRepositoryInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listTypes(?string $keyword = null): array;

    public function findTypeById(int $id): ?DictType;

    public function existsTypeCode(string $code, ?int $excludeId = null): bool;

    public function createType(array $payload): DictType;

    public function updateType(DictType $type, array $payload): DictType;

    public function deleteType(DictType $type): void;

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listItems(int $typeId, ?string $keyword = null): array;

    public function findItemById(int $id): ?DictItem;

    public function createItem(array $payload): DictItem;

    public function updateItem(DictItem $item, array $payload): DictItem;

    public function deleteItem(DictItem $item): void;

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listOptionsByCode(string $code): array;
}

