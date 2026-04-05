<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;

/**
 * 操作日志注解。
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class OperationLog
{
    public function __construct(
        public readonly ?string $module = null,
        public readonly ?string $action = null,
        public readonly bool $recordRequest = true,
        public readonly bool $recordResponse = true,
    ) {
    }
}
