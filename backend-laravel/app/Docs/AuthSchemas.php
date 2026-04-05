<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

/**
 * 认证模块文档组件定义。
 */
#[OA\Schema(
    schema: 'AuthLoginRequest',
    type: 'object',
    required: ['username', 'password'],
    properties: [
        new OA\Property(property: 'username', type: 'string', maxLength: 50, example: 'admin'),
        new OA\Property(property: 'password', type: 'string', minLength: 6, maxLength: 100, example: '123456'),
    ]
)]
#[OA\Schema(
    schema: 'AuthChangePasswordRequest',
    type: 'object',
    required: ['oldPassword', 'newPassword'],
    properties: [
        new OA\Property(property: 'oldPassword', type: 'string', minLength: 6, maxLength: 100, example: '123456'),
        new OA\Property(property: 'newPassword', type: 'string', minLength: 6, maxLength: 100, example: 'new_123456'),
    ]
)]
#[OA\Schema(
    schema: 'AuthPermissionBundle',
    type: 'object',
    properties: [
        new OA\Property(property: 'user', type: 'object', additionalProperties: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'menus', type: 'array', items: new OA\Items(type: 'object', additionalProperties: true)),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
    ]
)]
#[OA\Schema(
    schema: 'AuthSuccessEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuthPermissionBundle'),
    ]
)]
final class AuthSchemas
{
}

