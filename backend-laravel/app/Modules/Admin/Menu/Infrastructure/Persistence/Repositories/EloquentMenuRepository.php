<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Infrastructure\Persistence\Repositories;

use App\Models\Menu;
use App\Modules\Admin\Menu\Domain\Contracts\MenuRepositoryInterface;

final class EloquentMenuRepository implements MenuRepositoryInterface
{
    public function list(): array
    {
        return Menu::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static fn (Menu $menu) => [
                'id' => (int) $menu->id,
                'parentId' => $menu->parentId !== null ? (int) $menu->parentId : null,
                'name' => (string) $menu->name,
                'path' => $menu->path,
                'component' => $menu->component,
                'icon' => $menu->icon,
                'type' => (string) $menu->type,
                'permissionKey' => $menu->permissionKey,
                'sort' => (int) $menu->sort,
                'visible' => (bool) $menu->visible,
            ])
            ->values()
            ->all();
    }

    public function findById(int $id): ?Menu
    {
        return Menu::query()->find($id);
    }

    public function create(array $payload): Menu
    {
        /** @var Menu $menu */
        $menu = Menu::query()->create($payload);

        return $menu;
    }

    public function update(Menu $menu, array $payload): Menu
    {
        $menu->fill($payload);
        $menu->save();

        return $menu;
    }

    public function delete(Menu $menu): void
    {
        $menu->delete();
    }
}

