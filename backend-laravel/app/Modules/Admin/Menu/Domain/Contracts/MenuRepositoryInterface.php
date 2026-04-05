<?php

declare(strict_types=1);

namespace App\Modules\Admin\Menu\Domain\Contracts;

use App\Models\Menu;

interface MenuRepositoryInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array;

    public function findById(int $id): ?Menu;

    public function create(array $payload): Menu;

    public function update(Menu $menu, array $payload): Menu;

    public function delete(Menu $menu): void;
}

