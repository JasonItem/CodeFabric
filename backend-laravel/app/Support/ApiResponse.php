<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ApiCode;
use Illuminate\Http\JsonResponse;

/**
 * 统一 API 返回结构。
 */
final class ApiResponse
{
    /**
     * 成功响应。
     */
    public static function success(mixed $data = null, string $message = 'ok', ApiCode $code = ApiCode::SUCCESS): JsonResponse
    {
        return response()->json([
            'code' => $code->value,
            'message' => $message,
            'data' => $data,
        ], $code->value);
    }

    /**
     * 错误响应。
     */
    public static function error(string $message, ApiCode $code, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code->value,
            'message' => $message,
            'data' => $data,
        ], $code->value);
    }
}
