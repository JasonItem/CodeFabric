<?php

declare(strict_types=1);

namespace App\Modules\Admin\Dictionary\Http\Controllers;

use App\Attributes\ApiAuth;
use App\Attributes\OperationLog;
use App\Modules\Admin\Dictionary\Application\Services\DictionaryApplicationService;
use App\Modules\Admin\Dictionary\Http\Requests\CreateDictItemRequest;
use App\Modules\Admin\Dictionary\Http\Requests\CreateDictTypeRequest;
use App\Modules\Admin\Dictionary\Http\Requests\UpdateDictItemRequest;
use App\Modules\Admin\Dictionary\Http\Requests\UpdateDictTypeRequest;
use App\Shared\Http\ApiController;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 字典管理控制器：仅承担协议层职责。
 */
#[Prefix('dictionaries')]
final class DictionaryController extends ApiController
{
    public function __construct(
        private readonly DictionaryApplicationService $dictionaryApplicationService,
    ) {
    }

    #[Get('types')]
    #[ApiAuth(permission: 'system:dict:list')]
    public function listTypes(Request $request)
    {
        return $this->ok(
            $this->dictionaryApplicationService->listTypes($request->query('keyword'))
        );
    }

    #[Post('types')]
    #[ApiAuth(permission: 'system:dict:add')]
    #[OperationLog(module: '字典管理', action: '新增字典类型')]
    public function createType(CreateDictTypeRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->createType($request->validated()));
    }

    #[Put('types/{id}')]
    #[ApiAuth(permission: 'system:dict:edit')]
    #[OperationLog(module: '字典管理', action: '编辑字典类型')]
    public function updateType(int $id, UpdateDictTypeRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->updateType($id, $request->validated()));
    }

    #[Delete('types/{id}')]
    #[ApiAuth(permission: 'system:dict:delete')]
    #[OperationLog(module: '字典管理', action: '删除字典类型')]
    public function deleteType(int $id)
    {
        $this->dictionaryApplicationService->deleteType($id);

        return $this->ok(true);
    }

    #[Get('types/{typeId}/items')]
    #[ApiAuth(permission: 'system:dict:list')]
    public function listItems(int $typeId, Request $request)
    {
        return $this->ok(
            $this->dictionaryApplicationService->listItems($typeId, $request->query('keyword'))
        );
    }

    #[Post('types/{typeId}/items')]
    #[ApiAuth(permission: 'system:dict:add')]
    #[OperationLog(module: '字典管理', action: '新增字典项')]
    public function createItem(int $typeId, CreateDictItemRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->createItem($typeId, $request->validated()));
    }

    #[Put('items/{id}')]
    #[ApiAuth(permission: 'system:dict:edit')]
    #[OperationLog(module: '字典管理', action: '编辑字典项')]
    public function updateItem(int $id, UpdateDictItemRequest $request)
    {
        return $this->ok($this->dictionaryApplicationService->updateItem($id, $request->validated()));
    }

    #[Delete('items/{id}')]
    #[ApiAuth(permission: 'system:dict:delete')]
    #[OperationLog(module: '字典管理', action: '删除字典项')]
    public function deleteItem(int $id)
    {
        $this->dictionaryApplicationService->deleteItem($id);

        return $this->ok(true);
    }

    #[Get('options/{code}')]
    #[ApiAuth]
    public function optionsByCode(string $code)
    {
        return $this->ok($this->dictionaryApplicationService->optionsByCode($code));
    }
}
