<?php

declare(strict_types=1);

namespace App\Modules\Admin\LoginLog\Infrastructure\Persistence\Repositories;

use App\Models\LoginLog;
use App\Modules\Admin\LoginLog\Domain\Contracts\LoginLogRepositoryInterface;

/**
 * 基于 Eloquent 的登录日志仓储实现。
 */
final class EloquentLoginLogRepository implements LoginLogRepositoryInterface
{
    public function record(array $attributes): void
    {
        LoginLog::query()->create($attributes);
    }

    public function paginate(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, min(200, (int) ($query['pageSize'] ?? 10)));

        $builder = LoginLog::query()
            ->when(($query['ip'] ?? '') !== '', fn ($q) => $q->where('ip', 'like', '%'.trim((string) $query['ip']).'%'))
            ->when(($query['username'] ?? '') !== '', fn ($q) => $q->where('username', 'like', '%'.trim((string) $query['username']).'%'))
            ->when(isset($query['success']) && $query['success'] !== '', fn ($q) => $q->where('success', filter_var((string) $query['success'], FILTER_VALIDATE_BOOL)))
            ->when(($query['startTime'] ?? '') !== '', fn ($q) => $q->where('createdAt', '>=', (string) $query['startTime']))
            ->when(($query['endTime'] ?? '') !== '', fn ($q) => $q->where('createdAt', '<=', (string) $query['endTime']));

        $total = (int) $builder->count();
        $rows = $builder->orderByDesc('id')
            ->forPage($page, $pageSize)
            ->get()
            ->map(static fn (LoginLog $log) => [
                'id' => (int) $log->id,
                'userId' => $log->userId !== null ? (int) $log->userId : null,
                'username' => $log->username,
                'device' => $log->device,
                'browser' => $log->browser,
                'os' => $log->os,
                'ip' => $log->ip,
                'location' => $log->location ?: '未知地点',
                'userAgent' => $log->userAgent,
                'success' => (bool) $log->success,
                'message' => $log->message,
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

    public function deleteByIds(array $ids): int
    {
        return LoginLog::query()->whereIn('id', $ids)->delete();
    }

    public function deleteAll(): int
    {
        return LoginLog::query()->delete();
    }
}
