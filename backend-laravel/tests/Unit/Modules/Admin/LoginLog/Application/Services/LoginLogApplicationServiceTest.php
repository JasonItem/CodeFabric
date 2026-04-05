<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\LoginLog\Application\Services;

use App\Modules\Admin\LoginLog\Application\Services\LoginLogApplicationService;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LoginLogApplicationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function list_returns_paginated_result_from_repository(): void
    {
        $expected = [
            'list' => [['id' => 1, 'username' => 'admin']],
            'total' => 1,
            'page' => 1,
            'pageSize' => 20,
        ];

        $repository = Mockery::mock(LoginLogRepositoryInterface::class);
        $repository->shouldReceive('paginate')->once()->with(['keyword' => 'admin'])->andReturn($expected);

        $service = new LoginLogApplicationService($repository);
        $result = $service->list(['keyword' => 'admin']);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function clear_with_ids_deletes_selected_logs_only(): void
    {
        $repository = Mockery::mock(LoginLogRepositoryInterface::class);
        $repository->shouldReceive('deleteByIds')->once()->with([1, 2, 3])->andReturn(3);
        $repository->shouldNotReceive('deleteAll');

        $service = new LoginLogApplicationService($repository);

        $this->assertTrue($service->clear([1, 2, 3]));
    }

    #[Test]
    public function clear_without_ids_deletes_all_logs(): void
    {
        $repository = Mockery::mock(LoginLogRepositoryInterface::class);
        $repository->shouldReceive('deleteAll')->once()->andReturn(10);
        $repository->shouldNotReceive('deleteByIds');

        $service = new LoginLogApplicationService($repository);

        $this->assertTrue($service->clear());
    }
}
