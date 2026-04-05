<?php

declare(strict_types=1);

namespace Tests\Unit\CrossCutting;

use App\Attributes\ApiAuth;
use App\CrossCutting\ApiAuthHandler;
use App\Enums\ApiCode;
use App\Models\AdminUser;
use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use App\Modules\Admin\Auth\Domain\Services\AuthContextService;
use App\Support\JwtConfig;
use App\Support\JwtToken;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ApiAuthHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function missing_token_returns_unauthorized_code(): void
    {
        $this->setEnv('API_AUTH_ENABLED', 'true');
        $this->setEnv('API_STRICT_PERMISSION_ENABLED', 'true');

        $request = Request::create('/api/admin/test', 'GET');
        $this->bindAnnotatedRoute($request, new class
        {
            #[ApiAuth(permission: 'system:test:list')]
            public function secured(): void {}
        }, 'secured');

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authContextService = new AuthContextService($authRepository);
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        $jwtConfig = new JwtConfig();

        $handler = new ApiAuthHandler($authContextService, $authRepository, $jwtConfig);

        $called = false;
        $response = $handler->handle($request, function () use (&$called) {
            $called = true;
            return response()->json(['code' => 0, 'message' => 'ok', 'data' => true]);
        });

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($called);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(ApiCode::UNAUTHORIZED->value, $payload['code']);
    }

    #[Test]
    public function strict_permission_disabled_only_requires_login(): void
    {
        $this->setEnv('API_AUTH_ENABLED', 'true');
        $this->setEnv('API_STRICT_PERMISSION_ENABLED', 'false');
        $this->setEnv('JWT_SECRET', 'unit-test-secret-unit-test-secret-32');

        $request = Request::create('/api/admin/test', 'GET');
        $request->cookies->set('admin_token', JwtToken::encode(['uid' => 7], 'unit-test-secret-unit-test-secret-32'));
        $this->bindAnnotatedRoute($request, new class
        {
            #[ApiAuth(permission: 'system:test:list')]
            public function secured(): void {}
        }, 'secured');

        $user = new AdminUser();
        $user->id = 7;
        $user->username = 'admin';
        $user->status = 'ACTIVE';

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldReceive('findById')->once()->with(7)->andReturn($user);

        $authContextService = new AuthContextService($authRepository);
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        $jwtConfig = new JwtConfig();
        $handler = new ApiAuthHandler($authContextService, $authRepository, $jwtConfig);

        $called = false;
        $response = $handler->handle($request, function () use (&$called) {
            $called = true;
            return response()->json(['code' => 0, 'message' => 'ok', 'data' => true]);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(7, $request->attributes->get('adminUser')->id);
    }

    #[Test]
    public function strict_permission_enabled_blocks_forbidden_request(): void
    {
        $this->setEnv('API_AUTH_ENABLED', 'true');
        $this->setEnv('API_STRICT_PERMISSION_ENABLED', 'true');
        $this->setEnv('JWT_SECRET', 'unit-test-secret-unit-test-secret-32');

        $request = Request::create('/api/admin/test', 'GET');
        $request->cookies->set('admin_token', JwtToken::encode(['uid' => 8], 'unit-test-secret-unit-test-secret-32'));
        $this->bindAnnotatedRoute($request, new class
        {
            #[ApiAuth(permission: 'system:test:list')]
            public function secured(): void {}
        }, 'secured');

        $user = new AdminUser();
        $user->id = 8;
        $user->username = 'ops';
        $user->status = 'ACTIVE';

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $authRepository->shouldReceive('findById')->once()->with(8)->andReturn($user);

        $authRepository->shouldReceive('hasPermission')
            ->once()
            ->with([], 'system:test:list')
            ->andReturn(false);
        $authRepository->shouldReceive('getRoleIdsByUserId')
            ->once()
            ->with(8)
            ->andReturn([]);

        $authContextService = new AuthContextService($authRepository);
        config()->set('jwt.secret', 'unit-test-secret-unit-test-secret-32');
        $jwtConfig = new JwtConfig();
        $handler = new ApiAuthHandler($authContextService, $authRepository, $jwtConfig);

        $response = $handler->handle($request, fn () => response()->json(['code' => 0]));
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(ApiCode::FORBIDDEN->value, $payload['code']);
    }

    private function bindAnnotatedRoute(Request $request, object $controller, string $method): void
    {
        $routeMock = Mockery::mock();
        $routeMock->shouldReceive('getController')->andReturn($controller);
        $routeMock->shouldReceive('getActionMethod')->andReturn($method);
        $routeMock->shouldReceive('parameters')->andReturn([]);
        $request->setRouteResolver(static fn () => $routeMock);

        $this->app->instance('request', $request);
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
