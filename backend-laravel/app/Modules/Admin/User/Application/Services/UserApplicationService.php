<?php

declare(strict_types=1);

namespace App\Modules\Admin\User\Application\Services;

use App\Attributes\WithTransaction;
use App\Enums\ApiCode;
use App\Modules\Admin\User\Domain\Contracts\UserRepositoryInterface;
use App\Shared\Exceptions\ApiBusinessException;
use App\Shared\Application\ApplicationService;
use Illuminate\Support\Facades\Hash;

/**
 * 用户应用服务。
 */
final class UserApplicationService extends ApplicationService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        return $this->userRepository->listWithRoles();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function create(array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($payload) {
            $username = (string) $payload['username'];
            if ($this->userRepository->existsByUsername($username)) {
                throw new ApiBusinessException(ApiCode::CONFLICT, '账号已存在');
            }

            $roleIds = array_values(array_unique(array_map('intval', (array) ($payload['roleIds'] ?? []))));
            $user = $this->userRepository->create([
                'username' => $username,
                'nickname' => (string) $payload['nickname'],
                'passwordHash' => Hash::make((string) $payload['password']),
                'status' => (string) $payload['status'],
            ]);
            $this->userRepository->syncRoles($user, $roleIds);

            return $this->mapOne((int) $user->id);
        });
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    #[WithTransaction]
    public function update(int $id, array $payload): array
    {
        return $this->callWithAspects(__FUNCTION__, function () use ($id, $payload) {
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '用户不存在');
            }

            if (isset($payload['username'])) {
                $username = (string) $payload['username'];
                if ($this->userRepository->existsByUsername($username, $id)) {
                    throw new ApiBusinessException(ApiCode::CONFLICT, '账号已存在');
                }
                $payload['username'] = $username;
            }

            $updateData = [];
            foreach (['username', 'nickname', 'status'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $updateData[$field] = $payload[$field];
                }
            }

            if (array_key_exists('password', $payload) && (string) $payload['password'] !== '') {
                $updateData['passwordHash'] = Hash::make((string) $payload['password']);
            }

            $this->userRepository->update($user, $updateData);

            if (array_key_exists('roleIds', $payload)) {
                $roleIds = array_values(array_unique(array_map('intval', (array) $payload['roleIds'])));
                $this->userRepository->syncRoles($user, $roleIds);
            }

            return $this->mapOne($id);
        });
    }

    #[WithTransaction]
    public function delete(int $id): void
    {
        $this->callWithAspects(__FUNCTION__, function () use ($id): void {
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new ApiBusinessException(ApiCode::NOT_FOUND, '用户不存在');
            }
            $this->userRepository->delete($user);
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function mapOne(int $id): array
    {
        $rows = $this->userRepository->listWithRoles();
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        throw new ApiBusinessException(ApiCode::NOT_FOUND, '用户不存在');
    }
}
