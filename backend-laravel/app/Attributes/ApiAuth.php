<?php

declare(strict_types=1);

namespace App\Attributes;

use Attribute;

/**
 * 接口鉴权注解。
 *
 * @param string|null $permission 权限标识
 * @param bool $loginRequired 是否要求登录
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ApiAuth
{
    public function __construct(
        public readonly ?string $permission = null,
        public readonly bool $loginRequired = true,
    ) {
    }
}
