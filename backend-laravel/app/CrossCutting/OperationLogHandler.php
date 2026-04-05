<?php

declare(strict_types=1);

namespace App\CrossCutting;

use App\Attributes\OperationLog;
use App\Modules\Admin\OperationLog\Domain\Contracts\OperationLogRepositoryInterface;
use App\Support\ReflectRoute;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * 操作日志 AOP 中间件（基于注解）。
 */
final class OperationLogHandler
{
    public function __construct(
        private readonly OperationLogRepositoryInterface $operationLogRepository,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $method = ReflectRoute::method();
        $attrs = $method?->getAttributes(OperationLog::class) ?? [];

        if (empty($attrs)) {
            return $next($request);
        }

        /** @var OperationLog $meta */
        $meta = $attrs[0]->newInstance();

        $start = microtime(true);
        $response = $next($request);
        $duration = (int) round((microtime(true) - $start) * 1000);

        $user = $request->attributes->get('adminUser');
        $requestBody = $meta->recordRequest ? $this->captureRequestPayload($request) : null;
        $responseBody = $meta->recordResponse ? $this->captureResponsePayload($response) : null;

        try {
            $this->operationLogRepository->record([
                'userId' => $user?->id,
                'username' => $user?->username,
                'module' => $meta->module,
                'action' => $meta->action,
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'statusCode' => $response->getStatusCode(),
                'success' => $response->getStatusCode() < 400,
                'message' => $response->getStatusCode() < 400 ? '操作成功' : '操作失败',
                'ip' => $request->ip(),
                'location' => '未知地点',
                'userAgent' => (string) $request->userAgent(),
                'durationMs' => $duration,
                'requestBody' => $requestBody,
                'responseBody' => $responseBody,
                'createdAt' => now(),
            ]);
        } catch (\Throwable) {
            // 记录日志失败不影响主流程响应。
        }

        return $response;
    }

    private function truncate(?string $text, int $max = 10000): ?string
    {
        if ($text === null) {
            return null;
        }

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max).'...' : $text;
    }

    private function captureRequestPayload(Request $request): ?string
    {
        $payload = [];

        $routeParams = $request->route()?->parameters() ?? [];
        if (!empty($routeParams)) {
            $payload['params'] = $this->sanitizePayload($routeParams);
        }

        $query = $request->query();
        if (!empty($query)) {
            $payload['query'] = $this->sanitizePayload($query);
        }

        $body = $request->all();
        unset($body['files'], $body['file']);
        if (!empty($body)) {
            $payload['body'] = $this->sanitizePayload($body);
        }

        // 当框架未把 JSON body 解析到 input bag 时，补一层 raw body 兜底。
        if (empty($body)) {
            $raw = trim((string) $request->getContent());
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $payload['body'] = $this->sanitizePayload($decoded);
                }
            }
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($file) {
                $payload['file'] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
        }

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $fileRows = [];
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file) {
                        $fileRows[] = [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                        ];
                    }
                }
            }
            if (!empty($fileRows)) {
                $payload['files'] = $fileRows;
            }
        }

        if (empty($payload)) {
            return null;
        }

        return $this->encodePayload($payload);
    }

    private function captureResponsePayload(Response $response): ?string
    {
        try {
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                if (is_array($data)) {
                    return $this->encodePayload($this->sanitizePayload($data));
                }
            }

            $content = $response->getContent();
            if ($content === false || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $this->encodePayload($this->sanitizePayload($decoded));
            }

            return $this->truncate((string) $content);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $payload
     */
    private function encodePayload(array $payload): ?string
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        if (!is_string($encoded) || $encoded === '') {
            return null;
        }

        return $this->truncate($encoded);
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $payload
     * @return array<string,mixed>|array<int,mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'password',
            'oldPassword',
            'newPassword',
            'passwordHash',
            'token',
            'authorization',
            'admin_token',
        ];

        $result = $payload;

        foreach ($sensitiveKeys as $key) {
            if (Arr::has($result, $key)) {
                Arr::set($result, $key, '***');
            }
        }

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->sanitizePayload($value);
            }
        }

        return $result;
    }
}
