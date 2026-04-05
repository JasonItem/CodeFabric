<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Admin\AdminRbacSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminRbacSeeder::class,
        ]);
    }
}

