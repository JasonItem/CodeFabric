<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Dictionary\Application\Services;

use App\Enums\ApiCode;
use App\Models\DictItem;
use App\Models\DictType;
use App\Modules\Admin\Dictionary\Application\Services\DictionaryApplicationService;
use App\Modules\Admin\Dictionary\Domain\Contracts\DictionaryRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DictionaryApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function create_type_rejects_duplicate_code(): void
    {
        $repository = Mockery::mock(DictionaryRepositoryInterface::class);
        $repository->shouldReceive('existsTypeCode')->once()->with('gender')->andReturn(true);
        $repository->shouldNotReceive('createType');

        $service = new DictionaryApplicationService($repository);

        try {
            $service->createType(['name' => '性别', 'code' => 'gender']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::CONFLICT, $e->apiCode);
            $this->assertSame('字典类型编码已存在', $e->getMessage());
        }
    }

    #[Test]
    public function create_item_injects_type_id_and_returns_mapped_item(): void
    {
        $type = $this->makeType(9, '状态', 'status');
        $item = $this->makeItem(15, 9, '启用', 'ACTIVE');

        $repository = Mockery::mock(DictionaryRepositoryInterface::class);
        $repository->shouldReceive('findTypeById')->once()->with(9)->andReturn($type);
        $repository->shouldReceive('createItem')
            ->once()
            ->with([
                'label' => '启用',
                'value' => 'ACTIVE',
                'status' => true,
                'sort' => 1,
                'dictTypeId' => 9,
            ])
            ->andReturn($item);
        $repository->shouldReceive('listTypes')->once()->withNoArgs()->andReturn([
            ['id' => 9, 'name' => '状态', 'code' => 'status'],
        ]);
        $repository->shouldReceive('listItems')->once()->with(9)->andReturn([
            ['id' => 15, 'dictTypeId' => 9, 'label' => '启用', 'value' => 'ACTIVE'],
        ]);

        $service = new DictionaryApplicationService($repository);

        $result = $service->createItem(9, [
            'label' => '启用',
            'value' => 'ACTIVE',
            'status' => true,
            'sort' => 1,
        ]);

        $this->assertSame(15, $result['id']);
        $this->assertSame('ACTIVE', $result['value']);
    }

    #[Test]
    public function list_items_throws_not_found_when_type_does_not_exist(): void
    {
        $repository = Mockery::mock(DictionaryRepositoryInterface::class);
        $repository->shouldReceive('findTypeById')->once()->with(404)->andReturn(null);
        $repository->shouldNotReceive('listItems');

        $service = new DictionaryApplicationService($repository);

        try {
            $service->listItems(404);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('字典类型不存在', $e->getMessage());
        }
    }

    #[Test]
    public function update_item_throws_not_found_when_item_does_not_exist(): void
    {
        $repository = Mockery::mock(DictionaryRepositoryInterface::class);
        $repository->shouldReceive('findItemById')->once()->with(77)->andReturn(null);
        $repository->shouldNotReceive('updateItem');

        $service = new DictionaryApplicationService($repository);

        try {
            $service->updateItem(77, ['label' => 'missing']);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('字典项不存在', $e->getMessage());
        }
    }

    private function makeType(int $id, string $name, string $code): DictType
    {
        $type = new DictType();
        $type->id = $id;
        $type->name = $name;
        $type->code = $code;

        return $type;
    }

    private function makeItem(int $id, int $dictTypeId, string $label, string $value): DictItem
    {
        $item = new DictItem();
        $item->id = $id;
        $item->dictTypeId = $dictTypeId;
        $item->label = $label;
        $item->value = $value;

        return $item;
    }
}
