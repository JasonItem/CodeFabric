<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Attributes\WithRedisLock;
use App\Attributes\WithTransaction;
use App\CrossCutting\RedisLockHandler;
use App\CrossCutting\TransactionHandler;
use App\Shared\Application\ApplicationService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ApplicationServiceAspectsTest extends TestCase
{
    #[Test]
    public function call_with_aspects_executes_transaction_and_redis_lock(): void
    {
        $request = Request::create('/api/admin/auth/change-password', 'POST');
        $user = new \stdClass();
        $user->id = 99;
        $request->attributes->set('adminUser', $user);
        $this->app->instance('request', $request);

        $called = (object) [
            'transaction' => 0,
            'lock' => 0,
            'biz' => 0,
        ];

        $transactionHandler = new class($called, $this)
        {
            public function __construct(
                private readonly object $called,
                private readonly ApplicationServiceAspectsTest $test,
            ) {
            }

            public function run(WithTransaction $meta, \Closure $next): mixed
            {
                $this->called->transaction++;
                $this->test->assertSame('mysql', $meta->connection);
                return $next();
            }
        };

        $redisLockHandler = new class($called, $this)
        {
            public function __construct(
                private readonly object $called,
                private readonly ApplicationServiceAspectsTest $test,
            ) {
            }

            public function run(WithRedisLock $meta, array $replacements, \Closure $next): mixed
            {
                $this->called->lock++;
                $this->test->assertSame('lock:change-password:{uid}', $meta->key);
                $this->test->assertSame('99', $replacements['{uid}']);
                $this->test->assertSame('api/admin/auth/change-password', $replacements['{path}']);
                return $next();
            }
        };

        $this->app->instance(TransactionHandler::class, $transactionHandler);
        $this->app->instance(RedisLockHandler::class, $redisLockHandler);

        $service = new class($called) extends ApplicationService
        {
            public function __construct(private object $counter) {}

            #[WithTransaction]
            #[WithRedisLock(key: 'lock:change-password:{uid}', seconds: 5)]
            public function changePassword(): string
            {
                return $this->callWithAspects(__FUNCTION__, function (): string {
                    $this->counter->biz++;
                    return 'ok';
                });
            }
        };

        $result = $service->changePassword();

        $this->assertSame('ok', $result);
        $this->assertSame(1, $called->lock);
        $this->assertSame(1, $called->transaction);
        $this->assertSame(1, $called->biz);
    }
}
