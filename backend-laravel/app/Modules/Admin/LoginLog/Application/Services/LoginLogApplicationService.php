<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Application\Services;

use App\Attributes\WithTransaction;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;
use App\Shared\Application\ApplicationService;

/**
 * 登录日志应用服务。
 */
final class LoginLogApplicationService extends ApplicationService
{
    public function __construct(
        private readonly LoginLogRepositoryInterface $loginLogRepository,
    ) {
    }

    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function list(array $query): array
    {
        return $this->loginLogRepository->paginate($query);
    }

    /**
     * @param array<int> $ids
     */
    #[WithTransaction]
    public function clear(array $ids = []): bool
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($ids): bool {
            if (!empty($ids)) {
                $this->loginLogRepository->deleteByIds($ids);

                return true;
            }

            $this->loginLogRepository->deleteAll();

            return true;
        });
    }
}
