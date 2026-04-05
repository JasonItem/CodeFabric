<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

/**
 * CodeFabric 后端 OpenAPI 根定义。
 *
 * 说明：
 * - 使用 swagger-php Attributes 维护接口文档。
 * - 业务返回统一为 { code, message, data }，通过组件 Schema 复用。
 */
#[OA\Info(
    version: '1.0.0',
    title: 'CodeFabric Admin API',
    description: 'CodeFabric 管理后台后端接口文档（Laravel 13）'
)]
#[OA\Server(url: 'http://localhost:4000', description: '本地开发环境')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: '在 Authorization 请求头中传入 Bearer Token'
)]
#[OA\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'admin_token',
    description: '浏览器 Cookie 鉴权'
)]
#[OA\Schema(
    schema: 'ApiEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(
            property: 'data',
            oneOf: [
                new OA\Schema(type: 'object', additionalProperties: true),
                new OA\Schema(type: 'array', items: new OA\Items(type: 'object', additionalProperties: true)),
                new OA\Schema(type: 'string'),
                new OA\Schema(type: 'integer'),
                new OA\Schema(type: 'boolean'),
            ],
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'ApiErrorEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 2004),
        new OA\Property(property: 'message', type: 'string', example: '请求失败'),
        new OA\Property(property: 'data', nullable: true)
    ]
)]
final class OpenApiSpec
{
}

