<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;
use App\Shared\Application\ApplicationService;
use App\Shared\Exceptions\ApiBusinessException;

/**
 * 操作日志应用服务。
 */
final class OperationLogApplicationService extends ApplicationService
{
    public function __construct(
        private readonly OperationLogRepositoryInterface $operationLogRepository,
    ) {
    }

    /**
     * @param array<string,mixed> $query
     * @return array{list:array<int,array<string,mixed>>,total:int,page:int,pageSize:int}
     */
    public function list(array $query): array
    {
        return $this->operationLogRepository->paginate($query);
    }

    /**
     * @return array<string,mixed>
     */
    public function detail(int $id): array
    {
        $detail = $this->operationLogRepository->detail($id);
        if (!$detail) {
            throw new ApiBusinessException(ApiCode::NOT_FOUND, '操作日志不存在');
        }

        return $detail;
    }

    /**
     * @param array<int> $ids
     */
    #[WithTransaction]
    public function clear(array $ids = []): bool
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($ids): bool {
            if (!empty($ids)) {
                $this->operationLogRepository->deleteByIds($ids);

                return true;
            }

            $this->operationLogRepository->deleteAll();

            return true;
        });
    }
}
