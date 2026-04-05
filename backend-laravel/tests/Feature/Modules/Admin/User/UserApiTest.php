<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin\User;

use App\Models\AdminUser;
use Tests\Feature\Modules\Admin\AdminApiFeatureTestCase;

final class UserApiTest extends AdminApiFeatureTestCase
{
    public function test_user_list_requires_login(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401);
        $response->assertJsonPath('message', '未登录或登录已失效');
    }

    public function test_admin_can_list_users(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->apiGetAs('/api/admin/users', $token);

        $response->assertOk();
        $response->assertJsonFragment(['username' => 'admin']);
        $response->assertJsonFragment(['username' => 'ops']);
    }

    public function test_admin_can_create_user(): void
    {
        $token = $this->loginAndGetToken();
        $roleId = (int) \DB::table('Role')->where('code', 'OPS_ADMIN')->value('id');

        $response = $this->apiPostAs('/api/admin/users', [
            'username' => 'tester',
            'nickname' => '测试用户',
            'password' => 'tester123',
            'status' => 'ACTIVE',
            'roleIds' => [$roleId],
        ], $token);

        $response->assertOk();
        $response->assertJsonPath('data.username', 'tester');

        $this->assertDatabaseHas('AdminUser', [
            'username' => 'tester',
            'nickname' => '测试用户',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_create_user_with_duplicate_username_returns_conflict(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->apiPostAs('/api/admin/users', [
            'username' => 'admin',
            'nickname' => '重复用户',
            'password' => 'tester123',
            'status' => 'ACTIVE',
            'roleIds' => [],
        ], $token);

        $response->assertStatus(409);
        $response->assertJsonPath('message', '账号已存在');
    }

    public function test_ops_cannot_delete_user_without_permission(): void
    {
        $opsToken = $this->loginAndGetToken('ops', 'ops123456');
        $userId = (int) AdminUser::query()->where('username', 'admin')->value('id');

        $response = $this->apiDeleteAs("/api/admin/users/{$userId}", $opsToken);

        $response->assertStatus(403);
        $response->assertJsonPath('message', '没有权限执行该操作');
    }
}
