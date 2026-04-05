<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Enums\ApiCode;
use RuntimeException;

/**
 * 业务异常。
 *
 * 用于在应用层/领域层抛出可控错误，并在全局异常处理器中统一映射为
 * { code, message, data } 响应结构。
 */
final class ApiBusinessException extends RuntimeException
{
    public function __construct(
        public readonly ApiCode $apiCode,
        string $message,
        public readonly mixed $payload = null,
    ) {
        parent::__construct($message);
    }
}
