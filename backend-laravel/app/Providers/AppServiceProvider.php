<?php

namespace App\Providers;

use App\Modules\Admin\Auth\Domain\Contracts\AuthRepositoryInterface;
use App\Modules\Admin\Auth\Infrastructure\Persistence\Repositories\EloquentAuthRepository;
use App\Modules\Admin\Dictionary\Domain\Contracts\DictionaryRepositoryInterface;
use App\Modules\Admin\Dictionary\Infrastructure\Persistence\Repositories\EloquentDictionaryRepository;
use App\Modules\Admin\File\Domain\Contracts\FileFolderRepositoryInterface;
use App\Modules\Admin\File\Domain\Contracts\StoredFileRepositoryInterface;
use App\Modules\Admin\File\Infrastructure\Persistence\Repositories\EloquentFileFolderRepository;
use App\Modules\Admin\File\Infrastructure\Persistence\Repositories\EloquentStoredFileRepository;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;
use App\Modules\Admin\LoginLog\Infrastructure\Persistence\Repositories\EloquentLoginLogRepository;
use App\Modules\Admin\Menu\Domain\Contracts\MenuRepositoryInterface;
use App\Modules\Admin\Menu\Infrastructure\Persistence\Repositories\EloquentMenuRepository;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;
use App\Modules\Admin\OperationLog\Infrastructure\Persistence\Repositories\EloquentOperationLogRepository;
use App\Modules\Admin\Role\Domain\Contracts\RoleRepositoryInterface;
use App\Modules\Admin\Role\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use App\Modules\Admin\Table\Domain\Contracts\TableRepositoryInterface;
use App\Modules\Admin\Table\Infrastructure\Persistence\Repositories\DatabaseTableRepository;
use App\Modules\Admin\User\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Admin\User\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\Support\ApiResponse;
use App\Support\JwtConfig;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, EloquentAuthRepository::class);
        $this->app->bind(LoginLogRepositoryInterface::class, EloquentLoginLogRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(MenuRepositoryInterface::class, EloquentMenuRepository::class);
        $this->app->bind(DictionaryRepositoryInterface::class, EloquentDictionaryRepository::class);
        $this->app->bind(OperationLogRepositoryInterface::class, EloquentOperationLogRepository::class);
        $this->app->bind(FileFolderRepositoryInterface::class, EloquentFileFolderRepository::class);
        $this->app->bind(StoredFileRepositoryInterface::class, EloquentStoredFileRepository::class);
        $this->app->bind(TableRepositoryInterface::class, DatabaseTableRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $argv = $_SERVER['argv'] ?? [];
        $isTestingCommand = app()->runningInConsole() && str_contains(implode(' ', array_map('strval', $argv)), ' test');
        $isPhpUnitRuntime = defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__');
        $shouldSkipJwtFailFast = $isTestingCommand || $isPhpUnitRuntime || app()->runningUnitTests() || app()->environment('testing');

        if (!$shouldSkipJwtFailFast) {
            app(JwtConfig::class)->validateOrFail();
        }

        RateLimiter::for('admin-login', function (Request $request): Limit {
            $username = mb_strtolower(trim((string) $request->input('username', '')));
            $ip = (string) $request->ip();
            $key = "{$username}|{$ip}";
            $maxAttempts = max(1, (int) env('LOGIN_RATE_LIMIT_PER_MINUTE', 10));

            return Limit::perMinute($maxAttempts)
                ->by($key)
                ->response(function (Request $request) use ($username, $ip) {
                    Log::warning('登录限流触发', [
                        'username' => $username !== '' ? $username : null,
                        'ip' => $ip,
                        'path' => $request->path(),
                    ]);

                    return ApiResponse::error('登录尝试过于频繁，请稍后再试', \App\Enums\ApiCode::REPEAT_SUBMIT);
                });
        });
    }
}
