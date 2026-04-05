<?php

declare(strict_types=1);

namespace App\Modules\Admin\OperationLog\Infrastructure\Persistence\Repositories;

use App\Models\OperationLog;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;

/**
 * 基于 Eloquent 的操作日志仓储实现。
 */
final class EloquentOperationLogRepository implements OperationLogRepositoryInterface
{
    public function record(array $attributes): void
    {
        OperationLog::query()->create($attributes);
    }

    public function paginate(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, min(200, (int) ($query['pageSize'] ?? 10)));

        $builder = OperationLog::query()
            ->when(($query['path'] ?? '') !== '', fn ($q) => $q->where('path', 'like', '%'.trim((string) $query['path']).'%'))
            ->when(($query['module'] ?? '') !== '', fn ($q) => $q->where('module', 'like', '%'.trim((string) $query['module']).'%'))
            ->when(($query['username'] ?? '') !== '', fn ($q) => $q->where('username', 'like', '%'.trim((string) $query['username']).'%'))
            ->when(isset($query['success']) && $query['success'] !== '', fn ($q) => $q->where('success', filter_var((string) $query['success'], FILTER_VALIDATE_BOOL)))
            ->when(($query['startTime'] ?? '') !== '', fn ($q) => $q->where('createdAt', '>=', (string) $query['startTime']))
            ->when(($query['endTime'] ?? '') !== '', fn ($q) => $q->where('createdAt', '<=', (string) $query['endTime']));

        $total = (int) $builder->count();

        $rows = $builder->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get()
            ->map(static fn (OperationLog $log) => [
                'id' => (int) $log->id,
                'module' => $log->module,
                'action' => $log->action,
                'username' => $log->username,
                'ip' => $log->ip,
                'location' => $log->location ?: '未知地点',
                'path' => (string) $log->path,
                'method' => (string) $log->method,
                'statusCode' => (int) $log->statusCode,
                'success' => (bool) $log->success,
                'message' => $log->message,
                'durationMs' => $log->durationMs !== null ? (int) $log->durationMs : null,
                'createdAt' => (string) $log->createdAt,
            ])
            ->values()
            ->all();

        return [
            'list' => $rows,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    public function detail(int $id): ?array
    {
        $log = OperationLog::query()->find($id);
        if (!$log) {
            return null;
        }

        return [
            'id' => (int) $log->id,
            'userId' => $log->userId !== null ? (int) $log->userId : null,
            'module' => $log->module,
            'action' => $log->action,
            'username' => $log->username,
            'ip' => $log->ip,
            'location' => $log->location ?: '未知地点',
            'path' => (string) $log->path,
            'method' => (string) $log->method,
            'statusCode' => (int) $log->statusCode,
            'success' => (bool) $log->success,
            'message' => $log->message,
            'durationMs' => $log->durationMs !== null ? (int) $log->durationMs : null,
            'createdAt' => (string) $log->createdAt,
            'userAgent' => $log->userAgent,
            'requestBody' => $log->requestBody,
            'responseBody' => $log->responseBody,
        ];
    }

    public function deleteByIds(array $ids): int
    {
        return OperationLog::query()->whereIn('id', $ids)->delete();
    }

    public function deleteAll(): int
    {
        return OperationLog::query()->delete();
    }
}

