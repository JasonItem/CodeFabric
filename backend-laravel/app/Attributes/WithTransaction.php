<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;

/**
 * 事务注解：用于在控制器方法层做自动事务包裹。
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class WithTransaction
{
    public function __construct(
        public readonly string $connection = 'mysql',
    ) {
    }
}
