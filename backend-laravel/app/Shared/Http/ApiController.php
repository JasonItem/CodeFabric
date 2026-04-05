<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Enums\ApiCode;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * API 控制器基类：统一返回结构。
 */
abstract class ApiController
{
    protected function ok(mixed $data = null, string $message = 'ok'): JsonResponse
    {
        return ApiResponse::success($data, $message);
    }

    protected function fail(
        string $message,
        ApiCode $code = ApiCode::BAD_REQUEST,
        mixed $data = null
    ): JsonResponse {
        return ApiResponse::error($message, $code, $data);
    }
}
