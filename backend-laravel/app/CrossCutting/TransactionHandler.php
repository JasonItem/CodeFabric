<?php

declare(strict_types=1);

namespace App\CrossCutting;

use App\Attributes\WithTransaction;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * 事务横切处理器。
 */
final class TransactionHandler
{
    public function run(WithTransaction $meta, Closure $callback): mixed
    {
        return DB::connection($meta->connection)->transaction($callback);
    }
}
