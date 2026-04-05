<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Admin\Role;

use Tests\Feature\Modules\Admin\AdminApiFeatureTestCase;

final class RoleApiTest extends AdminApiFeatureTestCase
{
    public function test_ops_cannot_list_roles_without_permission(): void
    {
        $opsToken = $this->loginAndGetToken('ops', 'ops123456');

        $response = $this->apiGetAs('/api/admin/roles', $opsToken);

        $response->assertStatus(403);
        $response->assertJsonPath('message', '没有权限执行该操作');
    }

    public function test_admin_can_create_role(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->apiPostAs('/api/admin/roles', [
            'name' => '测试角色',
            'code' => 'TEST_ROLE',
            'description' => '用于接口测试',
        ], $token);

        $response->assertOk();
        $response->assertJsonPath('data.code', 'TEST_ROLE');

        $this->assertDatabaseHas('Role', [
            'code' => 'TEST_ROLE',
            'name' => '测试角色',
        ]);
    }

    public function test_create_role_with_duplicate_code_returns_conflict(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->apiPostAs('/api/admin/roles', [
            'name' => '重复角色',
            'code' => 'SUPER_ADMIN',
            'description' => '冲突测试',
        ], $token);

        $response->assertStatus(409);
        $response->assertJsonPath('message', '角色编码已存在');
    }

    public function test_admin_can_assign_permissions_to_role(): void
    {
        $token = $this->loginAndGetToken();
        $roleId = (int) \DB::table('Role')->where('code', 'OPS_ADMIN')->value('id');
        $menuIds = \DB::table('Menu')
            ->whereIn('permissionKey', ['system:user:list', 'system:user:delete'])
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $response = $this->apiPostAs("/api/admin/roles/{$roleId}/permissions", [
            'menuIds' => $menuIds,
        ], $token);

        $response->assertOk();
        $this->assertDatabaseHas('RoleMenu', [
            'roleId' => $roleId,
            'menuId' => $menuIds[0],
        ]);
    }
}
