<?php

declare(strict_types=1);

namespace Tests\Unit\CrossCutting;

use App\Attributes\OperationLog;
use App\CrossCutting\OperationLogHandler;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OperationLogHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function request_and_response_payload_are_recorded_and_sensitives_are_masked(): void
    {
        $request = Request::create('/api/admin/demo/8?keyword=test', 'POST', [
            'username' => 'admin',
            'password' => 'secret',
        ]);

        $controller = new class
        {
            #[OperationLog(module: '测试模块', action: '测试动作', recordRequest: true, recordResponse: true)]
            public function save(): void {}
        };
        $this->bindAnnotatedRoute($request, $controller, 'save', ['id' => 8]);

        $user = new \stdClass();
        $user->id = 7;
        $user->username = 'admin';
        $request->attributes->set('adminUser', $user);

        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (array $attributes): bool {
                $requestBody = json_decode((string) $attributes['requestBody'], true);
                $responseBody = json_decode((string) $attributes['responseBody'], true);

                $this->assertSame('测试模块', $attributes['module']);
                $this->assertSame('测试动作', $attributes['action']);
                $this->assertSame('POST', $attributes['method']);
                $this->assertSame('/api/admin/demo/8', $attributes['path']);
                $this->assertSame(7, $attributes['userId']);
                $this->assertSame('admin', $attributes['username']);
                $this->assertTrue($attributes['success']);
                $this->assertIsInt($attributes['durationMs']);
                $this->assertNotNull($attributes['requestBody']);
                $this->assertNotNull($attributes['responseBody']);

                $this->assertSame(8, $requestBody['params']['id']);
                $this->assertSame('test', $requestBody['query']['keyword']);
                $this->assertSame('admin', $requestBody['body']['username']);
                $this->assertSame('***', $requestBody['body']['password']);

                $this->assertSame(0, $responseBody['code']);
                $this->assertSame('ok', $responseBody['message']);
                $this->assertSame('***', $responseBody['data']['token']);

                return true;
            }));

        $handler = new OperationLogHandler($repository);
        $response = $handler->handle($request, fn () => response()->json([
            'code' => 0,
            'message' => 'ok',
            'data' => ['token' => 'jwt-token'],
        ]));

        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function when_no_annotation_it_will_not_record_log(): void
    {
        $request = Request::create('/api/admin/demo', 'GET');
        $this->bindAnnotatedRoute($request, new class
        {
            public function index(): void {}
        }, 'index', []);

        $repository = Mockery::mock(OperationLogRepositoryInterface::class);
        $repository->shouldNotReceive('record');

        $handler = new OperationLogHandler($repository);
        $response = $handler->handle($request, fn () => response()->json(['code' => 0]));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @param array<string,mixed> $params
     */
    private function bindAnnotatedRoute(Request $request, object $controller, string $method, array $params): void
    {
        $routeMock = Mockery::mock();
        $routeMock->shouldReceive('getController')->andReturn($controller);
        $routeMock->shouldReceive('getActionMethod')->andReturn($method);
        $routeMock->shouldReceive('parameters')->andReturn($params);
        $request->setRouteResolver(static fn () => $routeMock);

        $this->app->instance('request', $request);
    }
}

