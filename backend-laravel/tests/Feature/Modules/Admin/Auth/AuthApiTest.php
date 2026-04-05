<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin\Auth;

use Tests\Feature\Modules\Admin\AdminApiFeatureTestCase;

final class AuthApiTest extends AdminApiFeatureTestCase
{
    public function test_login_returns_bundle_and_sets_auth_cookie(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.user.username', 'admin');
        $response->assertJsonPath('data.user.nickname', '超级管理员');
        $response->assertJsonCount(1, 'data.roles');
    }

    public function test_login_with_wrong_password_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('code', 401);
        $response->assertJsonPath('message', '用户名或密码错误');
    }

    public function test_me_requires_login(): void
    {
        $response = $this->getJson('/api/admin/auth/me');

        $response->assertStatus(401);
        $response->assertJsonPath('message', '未登录或登录已失效');
    }

    public function test_me_returns_current_login_bundle(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->apiGetAs('/api/admin/auth/me', $token);

        $response->assertOk();
        $response->assertJsonPath('data.user.username', 'admin');
        $response->assertJsonPath('data.roles.0.code', 'SUPER_ADMIN');
        $response->assertJsonFragment(['username' => 'admin']);
        $this->assertContains('dashboard:view', (array) $response->json('data.permissions'));
    }
}
