<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\OperationLog\Application\Services;

use App\Enums\ApiCode;
use App\Modules\Admin\OperationLog\Application\Services\OperationLogApplicationService;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OperationLogApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function detail_returns_repository_result_when_present(): void
    {
        $expected = [
            'id' => 5,
            'module' => 'system:user',
            'action' => 'create',
        ];

        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldReceive('detail')->once()->with(5)->andReturn($expected);

        $service = new OperationLogApplicationService($repository);

        $this->assertSame($expected, $service->detail(5));
    }

    #[Test]
    public function detail_throws_not_found_when_log_does_not_exist(): void
    {
        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldReceive('detail')->once()->with(404)->andReturn(null);

        $service = new OperationLogApplicationService($repository);

        try {
            $service->detail(404);
            $this->fail('Expected ApiBusinessException was not thrown.');
        } catch (ApiBusinessException $e) {
            $this->assertSame(ApiCode::NOT_FOUND, $e->apiCode);
            $this->assertSame('操作日志不存在', $e->getMessage());
        }
    }

    #[Test]
    public function clear_with_ids_deletes_selected_logs_only(): void
    {
        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldReceive('deleteByIds')->once()->with([7, 8])->andReturn(2);
        $repository->shouldNotReceive('deleteAll');

        $service = new OperationLogApplicationService($repository);

        $this->assertTrue($service->clear([7, 8]));
    }

    #[Test]
    public function clear_without_ids_deletes_all_logs(): void
    {
        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldReceive('deleteAll')->once()->andReturn(99);
        $repository->shouldNotReceive('deleteByIds');

        $service = new OperationLogApplicationService($repository);

        $this->assertTrue($service->clear());
    }
}
