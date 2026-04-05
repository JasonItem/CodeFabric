<?php

use App\Enums\ApiCode;
use App\Shared\Exceptions\ApiBusinessException;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'anno.auth' => \App\CrossCutting\ApiAuthHandler::class,
            'anno.op' => \App\CrossCutting\OperationLogHandler::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error(
                $e->validator->errors()->first() ?: '参数校验失败',
                ApiCode::VALIDATION_ERROR,
                $e->errors()
            );
        });

        $exceptions->render(function (ApiBusinessException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->apiCode,
                $e->payload
            );
        });

        $exceptions->render(function (\Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $apiCode = ApiCode::fromHttpStatus($status);
            $message = $status >= 500 ? '服务器异常，请稍后重试' : ($e->getMessage() ?: '请求失败');

            return ApiResponse::error(
                $message,
                $apiCode
            );
        });
    })->create();
