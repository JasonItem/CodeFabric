<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRoleBrief',
    type: 'object',
    required: ['id', 'name', 'code'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: '超级管理员'),
        new OA\Property(property: 'code', type: 'string', example: 'SUPER_ADMIN'),
    ]
)]
#[OA\Schema(
    schema: 'UserItem',
    type: 'object',
    required: ['id', 'username', 'nickname', 'status', 'createdAt', 'roles'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'username', type: 'string', example: 'admin'),
        new OA\Property(property: 'nickname', type: 'string', example: '管理员'),
        new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'DISABLED'], example: 'ACTIVE'),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserRoleBrief')),
    ]
)]
#[OA\Schema(
    schema: 'UserListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserItem')),
    ]
)]
#[OA\Schema(
    schema: 'UserItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/UserItem'),
    ]
)]
#[OA\Schema(
    schema: 'UserCreateRequest',
    type: 'object',
    required: ['username', 'nickname', 'password', 'status'],
    properties: [
        new OA\Property(property: 'username', type: 'string', maxLength: 50, example: 'alice'),
        new OA\Property(property: 'nickname', type: 'string', maxLength: 100, example: 'Alice'),
        new OA\Property(property: 'password', type: 'string', minLength: 6, maxLength: 100, example: '123456'),
        new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'DISABLED'], example: 'ACTIVE'),
        new OA\Property(property: 'roleIds', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [1, 2]),
    ]
)]
#[OA\Schema(
    schema: 'UserUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'username', type: 'string', maxLength: 50, example: 'alice'),
        new OA\Property(property: 'nickname', type: 'string', maxLength: 100, example: 'Alice'),
        new OA\Property(property: 'password', type: 'string', minLength: 6, maxLength: 100, nullable: true, example: 'new_123456'),
        new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'DISABLED'], example: 'DISABLED'),
        new OA\Property(property: 'roleIds', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [2]),
    ]
)]
#[OA\Schema(
    schema: 'RoleItem',
    type: 'object',
    required: ['id', 'name', 'code', 'userCount', 'permissionCount', 'menuIds', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: '超级管理员'),
        new OA\Property(property: 'code', type: 'string', example: 'SUPER_ADMIN'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: '系统内置角色'),
        new OA\Property(property: 'userCount', type: 'integer', example: 3),
        new OA\Property(property: 'permissionCount', type: 'integer', example: 18),
        new OA\Property(property: 'menuIds', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [1, 2, 3]),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
    ]
)]
#[OA\Schema(
    schema: 'RoleListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoleItem')),
    ]
)]
#[OA\Schema(
    schema: 'RoleItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/RoleItem'),
    ]
)]
#[OA\Schema(
    schema: 'RoleCreateRequest',
    type: 'object',
    required: ['name', 'code'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, example: '运营'),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'OPERATOR'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: '运营角色'),
    ]
)]
#[OA\Schema(
    schema: 'RoleUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, example: '运营'),
        new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'OPERATOR'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: '运营角色'),
    ]
)]
#[OA\Schema(
    schema: 'RoleAssignPermissionsRequest',
    type: 'object',
    required: ['menuIds'],
    properties: [
        new OA\Property(property: 'menuIds', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [1, 2, 3]),
    ]
)]
#[OA\Schema(
    schema: 'MenuItem',
    type: 'object',
    required: ['id', 'parentId', 'name', 'type', 'sort', 'visible'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'name', type: 'string', example: '系统管理'),
        new OA\Property(property: 'path', type: 'string', nullable: true, example: '/system'),
        new OA\Property(property: 'component', type: 'string', nullable: true, example: 'system/index'),
        new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'setting'),
        new OA\Property(property: 'type', type: 'string', enum: ['DIRECTORY', 'MENU', 'BUTTON'], example: 'MENU'),
        new OA\Property(property: 'permissionKey', type: 'string', nullable: true, example: 'system:user:list'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 10),
        new OA\Property(property: 'visible', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'MenuListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MenuItem')),
    ]
)]
#[OA\Schema(
    schema: 'MenuItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/MenuItem'),
    ]
)]
#[OA\Schema(
    schema: 'MenuCreateRequest',
    type: 'object',
    required: ['name', 'type', 'sort', 'visible'],
    properties: [
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, minimum: 1, example: null),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: '用户管理'),
        new OA\Property(property: 'path', type: 'string', nullable: true, maxLength: 200, example: '/system/user'),
        new OA\Property(property: 'component', type: 'string', nullable: true, maxLength: 200, example: 'system/user/index'),
        new OA\Property(property: 'icon', type: 'string', nullable: true, maxLength: 100, example: 'user'),
        new OA\Property(property: 'type', type: 'string', enum: ['DIRECTORY', 'MENU', 'BUTTON'], example: 'MENU'),
        new OA\Property(property: 'permissionKey', type: 'string', nullable: true, maxLength: 120, example: 'system:user:list'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
        new OA\Property(property: 'visible', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'MenuUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, minimum: 1, example: null),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: '用户管理'),
        new OA\Property(property: 'path', type: 'string', nullable: true, maxLength: 200, example: '/system/user'),
        new OA\Property(property: 'component', type: 'string', nullable: true, maxLength: 200, example: 'system/user/index'),
        new OA\Property(property: 'icon', type: 'string', nullable: true, maxLength: 100, example: 'user'),
        new OA\Property(property: 'type', type: 'string', enum: ['DIRECTORY', 'MENU', 'BUTTON'], example: 'MENU'),
        new OA\Property(property: 'permissionKey', type: 'string', nullable: true, maxLength: 120, example: 'system:user:list'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
        new OA\Property(property: 'visible', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'ClearByIdsRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [1, 2, 3]),
    ]
)]
#[OA\Schema(
    schema: 'DictTypeItem',
    type: 'object',
    required: ['id', 'name', 'code', 'status', 'sort', 'createdAt', 'itemCount'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: '用户状态'),
        new OA\Property(property: 'code', type: 'string', example: 'USER_STATUS'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: '用户状态字典'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'itemCount', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'DictTypeListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DictTypeItem')),
    ]
)]
#[OA\Schema(
    schema: 'DictTypeItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/DictTypeItem'),
    ]
)]
#[OA\Schema(
    schema: 'DictTypeCreateRequest',
    type: 'object',
    required: ['name', 'code', 'status', 'sort'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: '用户状态'),
        new OA\Property(property: 'code', type: 'string', maxLength: 100, example: 'USER_STATUS'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: '用户状态字典'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'DictTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: '用户状态'),
        new OA\Property(property: 'code', type: 'string', maxLength: 100, example: 'USER_STATUS'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: '用户状态字典'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'DictItem',
    type: 'object',
    required: ['id', 'dictTypeId', 'label', 'value', 'status', 'sort', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'dictTypeId', type: 'integer', example: 1),
        new OA\Property(property: 'label', type: 'string', example: '启用'),
        new OA\Property(property: 'value', type: 'string', example: 'ACTIVE'),
        new OA\Property(property: 'tagType', type: 'string', nullable: true, example: 'success'),
        new OA\Property(property: 'tagClass', type: 'string', nullable: true, example: 'is-success'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
    ]
)]
#[OA\Schema(
    schema: 'DictItemListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DictItem')),
    ]
)]
#[OA\Schema(
    schema: 'DictItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/DictItem'),
    ]
)]
#[OA\Schema(
    schema: 'DictItemCreateRequest',
    type: 'object',
    required: ['label', 'value', 'status', 'sort'],
    properties: [
        new OA\Property(property: 'label', type: 'string', maxLength: 100, example: '启用'),
        new OA\Property(property: 'value', type: 'string', maxLength: 100, example: 'ACTIVE'),
        new OA\Property(property: 'tagType', type: 'string', maxLength: 50, nullable: true, example: 'success'),
        new OA\Property(property: 'tagClass', type: 'string', maxLength: 100, nullable: true, example: 'is-success'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'DictItemUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'label', type: 'string', maxLength: 100, example: '启用'),
        new OA\Property(property: 'value', type: 'string', maxLength: 100, example: 'ACTIVE'),
        new OA\Property(property: 'tagType', type: 'string', maxLength: 50, nullable: true, example: 'success'),
        new OA\Property(property: 'tagClass', type: 'string', maxLength: 100, nullable: true, example: 'is-success'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'DictOptionItem',
    type: 'object',
    required: ['id', 'label', 'value', 'sort'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'label', type: 'string', example: '启用'),
        new OA\Property(property: 'value', type: 'string', example: 'ACTIVE'),
        new OA\Property(property: 'tagType', type: 'string', nullable: true, example: 'success'),
        new OA\Property(property: 'tagClass', type: 'string', nullable: true, example: 'is-success'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 1),
    ]
)]
#[OA\Schema(
    schema: 'DictOptionListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DictOptionItem')),
    ]
)]
#[OA\Schema(
    schema: 'LoginLogItem',
    type: 'object',
    required: ['id', 'location', 'success', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1001),
        new OA\Property(property: 'userId', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin'),
        new OA\Property(property: 'device', type: 'string', nullable: true, example: 'Desktop'),
        new OA\Property(property: 'browser', type: 'string', nullable: true, example: 'Chrome'),
        new OA\Property(property: 'os', type: 'string', nullable: true, example: 'macOS'),
        new OA\Property(property: 'ip', type: 'string', nullable: true, example: '127.0.0.1'),
        new OA\Property(property: 'location', type: 'string', example: '未知地点'),
        new OA\Property(property: 'userAgent', type: 'string', nullable: true, example: 'Mozilla/5.0'),
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: '登录成功'),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
    ]
)]
#[OA\Schema(
    schema: 'LoginLogPageData',
    type: 'object',
    required: ['list', 'total', 'page', 'pageSize'],
    properties: [
        new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/LoginLogItem')),
        new OA\Property(property: 'total', type: 'integer', example: 120),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'pageSize', type: 'integer', example: 20),
    ]
)]
#[OA\Schema(
    schema: 'LoginLogListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/LoginLogPageData'),
    ]
)]
#[OA\Schema(
    schema: 'OperationLogListItem',
    type: 'object',
    required: ['id', 'path', 'method', 'statusCode', 'success', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2001),
        new OA\Property(property: 'module', type: 'string', nullable: true, example: '用户管理'),
        new OA\Property(property: 'action', type: 'string', nullable: true, example: '编辑用户'),
        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin'),
        new OA\Property(property: 'ip', type: 'string', nullable: true, example: '127.0.0.1'),
        new OA\Property(property: 'location', type: 'string', nullable: true, example: '未知地点'),
        new OA\Property(property: 'path', type: 'string', example: '/api/admin/users/1'),
        new OA\Property(property: 'method', type: 'string', example: 'PUT'),
        new OA\Property(property: 'statusCode', type: 'integer', example: 200),
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'ok'),
        new OA\Property(property: 'durationMs', type: 'integer', nullable: true, example: 38),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
    ]
)]
#[OA\Schema(
    schema: 'OperationLogPageData',
    type: 'object',
    required: ['list', 'total', 'page', 'pageSize'],
    properties: [
        new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/OperationLogListItem')),
        new OA\Property(property: 'total', type: 'integer', example: 256),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'pageSize', type: 'integer', example: 20),
    ]
)]
#[OA\Schema(
    schema: 'OperationLogListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/OperationLogPageData'),
    ]
)]
#[OA\Schema(
    schema: 'OperationLogDetailItem',
    type: 'object',
    required: ['id', 'path', 'method', 'statusCode', 'success', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2001),
        new OA\Property(property: 'userId', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'module', type: 'string', nullable: true, example: '用户管理'),
        new OA\Property(property: 'action', type: 'string', nullable: true, example: '编辑用户'),
        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin'),
        new OA\Property(property: 'ip', type: 'string', nullable: true, example: '127.0.0.1'),
        new OA\Property(property: 'location', type: 'string', nullable: true, example: '未知地点'),
        new OA\Property(property: 'path', type: 'string', example: '/api/admin/users/1'),
        new OA\Property(property: 'method', type: 'string', example: 'PUT'),
        new OA\Property(property: 'statusCode', type: 'integer', example: 200),
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'ok'),
        new OA\Property(property: 'durationMs', type: 'integer', nullable: true, example: 38),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'userAgent', type: 'string', nullable: true, example: 'Mozilla/5.0'),
        new OA\Property(property: 'requestBody', nullable: true),
        new OA\Property(property: 'responseBody', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'OperationLogDetailEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/OperationLogDetailItem'),
    ]
)]
#[OA\Schema(
    schema: 'FileFolderItem',
    type: 'object',
    required: ['id', 'parentId', 'name', 'sort', 'createdAt', 'updatedAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'name', type: 'string', example: '演示图片'),
        new OA\Property(property: 'sort', type: 'integer', example: 10),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', example: '2026-04-05 10:00:00'),
    ]
)]
#[OA\Schema(
    schema: 'FileFolderListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FileFolderItem')),
    ]
)]
#[OA\Schema(
    schema: 'FileFolderItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/FileFolderItem'),
    ]
)]
#[OA\Schema(
    schema: 'FileFolderCreateRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, minimum: 1, example: null),
        new OA\Property(property: 'name', type: 'string', maxLength: 120, example: '产品图'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, nullable: true, example: 0),
    ]
)]
#[OA\Schema(
    schema: 'FileFolderUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'parentId', type: 'integer', nullable: true, minimum: 1, example: null),
        new OA\Property(property: 'name', type: 'string', maxLength: 120, example: '产品图'),
        new OA\Property(property: 'sort', type: 'integer', minimum: 0, example: 10),
    ]
)]
#[OA\Schema(
    schema: 'FileItem',
    type: 'object',
    required: ['id', 'folderId', 'source', 'kind', 'name', 'originalName', 'size', 'relativePath', 'url', 'createdAt', 'updatedAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 101),
        new OA\Property(property: 'folderId', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'source', type: 'string', enum: ['ADMIN', 'USER'], example: 'ADMIN'),
        new OA\Property(property: 'kind', type: 'string', enum: ['IMAGE', 'VIDEO', 'FILE'], example: 'IMAGE'),
        new OA\Property(property: 'name', type: 'string', example: 'logo'),
        new OA\Property(property: 'originalName', type: 'string', example: 'logo.png'),
        new OA\Property(property: 'ext', type: 'string', nullable: true, example: 'png'),
        new OA\Property(property: 'mimeType', type: 'string', nullable: true, example: 'image/png'),
        new OA\Property(property: 'size', type: 'integer', example: 10240),
        new OA\Property(property: 'relativePath', type: 'string', example: 'uploads/2026/04/05/uuid.png'),
        new OA\Property(property: 'url', type: 'string', example: '/storage/uploads/2026/04/05/uuid.png'),
        new OA\Property(property: 'createdById', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'createdByName', type: 'string', nullable: true, example: 'admin'),
        new OA\Property(property: 'createdAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'folder', ref: '#/components/schemas/FileFolderItem', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'FileListData',
    type: 'object',
    required: ['list', 'total', 'page', 'pageSize'],
    properties: [
        new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/FileItem')),
        new OA\Property(property: 'total', type: 'integer', example: 88),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'pageSize', type: 'integer', example: 20),
    ]
)]
#[OA\Schema(
    schema: 'FileListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/FileListData'),
    ]
)]
#[OA\Schema(
    schema: 'FileItemEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/FileItem'),
    ]
)]
#[OA\Schema(
    schema: 'FileUploadEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FileItem')),
    ]
)]
#[OA\Schema(
    schema: 'FileUploadRequest',
    type: 'object',
    required: ['files'],
    properties: [
        new OA\Property(property: 'files', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
        new OA\Property(property: 'folderId', type: 'integer', nullable: true, minimum: 1, example: 1),
        new OA\Property(property: 'source', type: 'string', enum: ['ADMIN', 'USER'], nullable: true, example: 'ADMIN'),
    ]
)]
#[OA\Schema(
    schema: 'FileUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'folderId', type: 'integer', nullable: true, minimum: 1, example: 2),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'new-name'),
    ]
)]
#[OA\Schema(
    schema: 'FileBatchDeleteRequest',
    type: 'object',
    required: ['ids'],
    properties: [
        new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer', minimum: 1), example: [11, 12, 13]),
    ]
)]
#[OA\Schema(
    schema: 'TableListItem',
    type: 'object',
    required: ['tableName', 'tableRows', 'dataLength', 'indexLength'],
    properties: [
        new OA\Property(property: 'tableName', type: 'string', example: 'AdminUser'),
        new OA\Property(property: 'tableComment', type: 'string', nullable: true, example: '后台用户表'),
        new OA\Property(property: 'engine', type: 'string', nullable: true, example: 'InnoDB'),
        new OA\Property(property: 'tableRows', type: 'integer', example: 2),
        new OA\Property(property: 'dataLength', type: 'integer', example: 16384),
        new OA\Property(property: 'indexLength', type: 'integer', example: 16384),
        new OA\Property(property: 'createTime', type: 'string', nullable: true, example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'updateTime', type: 'string', nullable: true, example: '2026-04-05 10:00:00'),
        new OA\Property(property: 'collation', type: 'string', nullable: true, example: 'utf8mb4_unicode_ci'),
    ]
)]
#[OA\Schema(
    schema: 'TableListData',
    type: 'object',
    required: ['list', 'total'],
    properties: [
        new OA\Property(property: 'list', type: 'array', items: new OA\Items(ref: '#/components/schemas/TableListItem')),
        new OA\Property(property: 'total', type: 'integer', example: 18),
    ]
)]
#[OA\Schema(
    schema: 'TableListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TableListData'),
    ]
)]
#[OA\Schema(
    schema: 'TableColumnItem',
    type: 'object',
    required: ['columnName', 'columnType', 'dataType', 'isNullable', 'ordinalPosition'],
    properties: [
        new OA\Property(property: 'columnName', type: 'string', example: 'id'),
        new OA\Property(property: 'columnType', type: 'string', example: 'int unsigned'),
        new OA\Property(property: 'dataType', type: 'string', example: 'int'),
        new OA\Property(property: 'isNullable', type: 'string', enum: ['YES', 'NO'], example: 'NO'),
        new OA\Property(property: 'columnDefault', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'columnComment', type: 'string', nullable: true, example: '主键'),
        new OA\Property(property: 'columnKey', type: 'string', nullable: true, example: 'PRI'),
        new OA\Property(property: 'extra', type: 'string', nullable: true, example: 'auto_increment'),
        new OA\Property(property: 'characterSetName', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'collationName', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'ordinalPosition', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'TableColumnListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TableColumnItem')),
    ]
)]
#[OA\Schema(
    schema: 'TableCreateSqlItem',
    type: 'object',
    required: ['tableName', 'createSql'],
    properties: [
        new OA\Property(property: 'tableName', type: 'string', example: 'AdminUser'),
        new OA\Property(property: 'createSql', type: 'string', example: 'CREATE TABLE ...'),
    ]
)]
#[OA\Schema(
    schema: 'TableCreateSqlEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TableCreateSqlItem'),
    ]
)]
#[OA\Schema(
    schema: 'TableIndexColumnItem',
    type: 'object',
    required: ['columnName', 'seqInIndex'],
    properties: [
        new OA\Property(property: 'columnName', type: 'string', example: 'username'),
        new OA\Property(property: 'seqInIndex', type: 'integer', example: 1),
        new OA\Property(property: 'subPart', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'collation', type: 'string', nullable: true, example: 'A'),
    ]
)]
#[OA\Schema(
    schema: 'TableIndexItem',
    type: 'object',
    required: ['indexName', 'unique', 'indexType', 'columns'],
    properties: [
        new OA\Property(property: 'indexName', type: 'string', example: 'PRIMARY'),
        new OA\Property(property: 'unique', type: 'boolean', example: true),
        new OA\Property(property: 'indexType', type: 'string', example: 'BTREE'),
        new OA\Property(property: 'indexComment', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'columns', type: 'array', items: new OA\Items(ref: '#/components/schemas/TableIndexColumnItem')),
    ]
)]
#[OA\Schema(
    schema: 'TableIndexListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TableIndexItem')),
    ]
)]
#[OA\Schema(
    schema: 'TableForeignKeyItem',
    type: 'object',
    required: ['constraintName', 'columnName', 'referencedTableName', 'referencedColumnName', 'updateRule', 'deleteRule'],
    properties: [
        new OA\Property(property: 'constraintName', type: 'string', example: 'fk_user_role'),
        new OA\Property(property: 'columnName', type: 'string', example: 'roleId'),
        new OA\Property(property: 'referencedTableName', type: 'string', example: 'Role'),
        new OA\Property(property: 'referencedColumnName', type: 'string', example: 'id'),
        new OA\Property(property: 'updateRule', type: 'string', example: 'CASCADE'),
        new OA\Property(property: 'deleteRule', type: 'string', example: 'RESTRICT'),
    ]
)]
#[OA\Schema(
    schema: 'TableForeignKeyListEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TableForeignKeyItem')),
    ]
)]
#[OA\Schema(
    schema: 'TableExportItem',
    type: 'object',
    required: ['tableName', 'fileName', 'sql'],
    properties: [
        new OA\Property(property: 'tableName', type: 'string', example: 'AdminUser'),
        new OA\Property(property: 'fileName', type: 'string', example: 'AdminUser.sql'),
        new OA\Property(property: 'sql', type: 'string', example: 'SET NAMES utf8mb4;'),
    ]
)]
#[OA\Schema(
    schema: 'TableExportEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TableExportItem'),
    ]
)]
#[OA\Schema(
    schema: 'TableExportAllItem',
    type: 'object',
    required: ['fileName', 'sql', 'tableCount'],
    properties: [
        new OA\Property(property: 'fileName', type: 'string', example: 'all-tables-20260405-100000.sql'),
        new OA\Property(property: 'sql', type: 'string', example: 'SET NAMES utf8mb4;'),
        new OA\Property(property: 'tableCount', type: 'integer', example: 12),
    ]
)]
#[OA\Schema(
    schema: 'TableExportAllEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TableExportAllItem'),
    ]
)]
#[OA\Schema(
    schema: 'TableImportRequest',
    type: 'object',
    required: ['file'],
    properties: [
        new OA\Property(property: 'file', type: 'string', format: 'binary'),
        new OA\Property(property: 'mode', type: 'string', enum: ['strict', 'skip-create'], nullable: true, example: 'skip-create'),
    ]
)]
#[OA\Schema(
    schema: 'TableImportItem',
    type: 'object',
    required: ['count', 'skippedCount', 'skippedTables', 'mode', 'fileName'],
    properties: [
        new OA\Property(property: 'count', type: 'integer', example: 10),
        new OA\Property(property: 'skippedCount', type: 'integer', example: 2),
        new OA\Property(property: 'skippedTables', type: 'array', items: new OA\Items(type: 'string'), example: ['AdminUser']),
        new OA\Property(property: 'mode', type: 'string', enum: ['strict', 'skip-create'], example: 'skip-create'),
        new OA\Property(property: 'fileName', type: 'string', example: 'backup.sql'),
    ]
)]
#[OA\Schema(
    schema: 'TableImportEnvelope',
    type: 'object',
    required: ['code', 'message', 'data'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'ok'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TableImportItem'),
    ]
)]
#[OA\Schema(
    schema: 'TableExecuteSqlRequest',
    type: 'object',
    required: ['sql'],
    properties: [
        new OA\Property(property: 'sql', type: 'string', example: 'CREATE TABLE demo (id BIGINT PRIMARY KEY);'),
    ]
)]
final class AdminSchemas
{
}
