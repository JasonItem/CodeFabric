<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Infrastructure\Persistence\Repositories;

use App\Models\DictItem;
use App\Models\DictType;
use App\Modules\Admin\Dictionary\Domain\Contracts\DictionaryRepositoryInterface;

final class EloquentDictionaryRepository implements DictionaryRepositoryInterface
{
    public function listTypes(?string $keyword = null): array
    {
        return DictType::query()
            ->withCount('items')
            ->when($keyword !== null && trim($keyword) !== '', function ($q) use ($keyword) {
                $kw = trim((string) $keyword);
                $q->where('name', 'like', "%{$kw}%")->orWhere('code', 'like', "%{$kw}%");
            })
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (DictType $type) => [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
                'code' => (string) $type->code,
                'description' => $type->description,
                'status' => (bool) $type->status,
                'sort' => (int) $type->sort,
                'createdAt' => (string) $type->createdAt,
                'itemCount' => (int) $type->items_count,
            ])
            ->values()
            ->all();
    }

    public function findTypeById(int $id): ?DictType
    {
        return DictType::query()->find($id);
    }

    public function existsTypeCode(string $code, ?int $excludeId = null): bool
    {
        return DictType::query()
            ->where('code', $code)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function createType(array $payload): DictType
    {
        /** @var DictType $type */
        $type = DictType::query()->create($payload);

        return $type;
    }

    public function updateType(DictType $type, array $payload): DictType
    {
        $type->fill($payload);
        $type->save();

        return $type;
    }

    public function deleteType(DictType $type): void
    {
        $type->items()->delete();
        $type->delete();
    }

    public function listItems(int $typeId, ?string $keyword = null): array
    {
        return DictItem::query()
            ->where('dictTypeId', $typeId)
            ->when($keyword !== null && trim($keyword) !== '', function ($q) use ($keyword) {
                $kw = trim((string) $keyword);
                $q->where('label', 'like', "%{$kw}%")->orWhere('value', 'like', "%{$kw}%");
            })
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (DictItem $item) => [
                'id' => (int) $item->id,
                'dictTypeId' => (int) $item->dictTypeId,
                'label' => (string) $item->label,
                'value' => (string) $item->value,
                'tagType' => $item->tagType,
                'tagClass' => $item->tagClass,
                'status' => (bool) $item->status,
                'sort' => (int) $item->sort,
                'createdAt' => (string) $item->createdAt,
            ])
            ->values()
            ->all();
    }

    public function findItemById(int $id): ?DictItem
    {
        return DictItem::query()->find($id);
    }

    public function createItem(array $payload): DictItem
    {
        /** @var DictItem $item */
        $item = DictItem::query()->create($payload);

        return $item;
    }

    public function updateItem(DictItem $item, array $payload): DictItem
    {
        $item->fill($payload);
        $item->save();

        return $item;
    }

    public function deleteItem(DictItem $item): void
    {
        $item->delete();
    }

    public function listOptionsByCode(string $code): array
    {
        $type = DictType::query()->where('code', $code)->first();
        if (!$type) {
            return [];
        }

        return DictItem::query()
            ->where('dictTypeId', $type->id)
            ->where('status', true)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (DictItem $item) => [
                'id' => (int) $item->id,
                'label' => (string) $item->label,
                'value' => (string) $item->value,
                'tagType' => $item->tagType,
                'tagClass' => $item->tagClass,
                'sort' => (int) $item->sort,
            ])
            ->values()
            ->all();
    }
}

