<?php

declare(strict_types=1);

namespace Database\Seeders\Admin;

use App\Models\AdminUser;
use App\Models\DictItem;
use App\Models\DictType;
use App\Models\FileFolder;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedMenus();
            $this->seedDictionaries();
            $this->seedFileFolders();
            $this->seedUsersAndRoles();
        });
    }

    private function seedMenus(): void
    {
        DB::table('RoleMenu')->delete();
        DB::table('UserRole')->delete();
        Menu::query()->delete();

        $dashboard = Menu::query()->create([
            'parentId' => null,
            'name' => '仪表盘',
            'path' => '/',
            'component' => 'dashboard/index',
            'icon' => 'LayoutDashboard',
            'type' => 'MENU',
            'permissionKey' => 'dashboard:view',
            'sort' => 1,
            'visible' => true,
        ]);

        $system = Menu::query()->create([
            'parentId' => null,
            'name' => '系统管理',
            'path' => '/system',
            'component' => null,
            'icon' => 'Settings',
            'type' => 'DIRECTORY',
            'permissionKey' => 'system:view',
            'sort' => 10,
            'visible' => true,
        ]);

        $userMenu = Menu::query()->create([
            'parentId' => (int) $system->id,
            'name' => '用户管理',
            'path' => '/users',
            'component' => 'rbac/users',
            'icon' => 'Users',
            'type' => 'MENU',
            'permissionKey' => 'system:user:page',
            'sort' => 11,
            'visible' => true,
        ]);

        $roleMenu = Menu::query()->create([
            'parentId' => (int) $system->id,
            'name' => '角色管理',
            'path' => '/roles',
            'component' => 'rbac/roles',
            'icon' => 'Shield',
            'type' => 'MENU',
            'permissionKey' => 'system:role:page',
            'sort' => 12,
            'visible' => true,
        ]);

        $menuMenu = Menu::query()->create([
            'parentId' => (int) $system->id,
            'name' => '菜单权限',
            'path' => '/menus',
            'component' => 'rbac/menus',
            'icon' => 'Menu',
            'type' => 'MENU',
            'permissionKey' => 'system:menu:page',
            'sort' => 13,
            'visible' => true,
        ]);

        $fileMenu = Menu::query()->create([
            'parentId' => (int) $system->id,
            'name' => '文件管理',
            'path' => '/files',
            'component' => 'rbac/files-page',
            'icon' => 'FolderOpen',
            'type' => 'MENU',
            'permissionKey' => 'system:file:page',
            'sort' => 17,
            'visible' => true,
        ]);

        $systemLogDir = Menu::query()->create([
            'parentId' => null,
            'name' => '系统日志',
            'path' => '/log',
            'component' => null,
            'icon' => 'Bug',
            'type' => 'DIRECTORY',
            'permissionKey' => 'log:view',
            'sort' => 20,
            'visible' => true,
        ]);

        $operationLog = Menu::query()->create([
            'parentId' => (int) $systemLogDir->id,
            'name' => '操作日志',
            'path' => '/operation-logs',
            'component' => 'rbac/operation-logs',
            'icon' => 'Logs',
            'type' => 'MENU',
            'permissionKey' => 'system:operation-log:page',
            'sort' => 21,
            'visible' => true,
        ]);

        $loginLog = Menu::query()->create([
            'parentId' => (int) $systemLogDir->id,
            'name' => '登录日志',
            'path' => '/login-logs',
            'component' => 'rbac/login-logs',
            'icon' => 'LogIn',
            'type' => 'MENU',
            'permissionKey' => 'system:login-log:page',
            'sort' => 22,
            'visible' => true,
        ]);

        $devTools = Menu::query()->create([
            'parentId' => null,
            'name' => '开发工具',
            'path' => '/dev-tools',
            'component' => null,
            'icon' => 'Code2',
            'type' => 'DIRECTORY',
            'permissionKey' => 'dev:tools:view',
            'sort' => 30,
            'visible' => true,
        ]);

        $dictMenu = Menu::query()->create([
            'parentId' => (int) $devTools->id,
            'name' => '字典管理',
            'path' => '/dictionaries',
            'component' => 'rbac/dictionaries',
            'icon' => 'BookText',
            'type' => 'MENU',
            'permissionKey' => 'system:dict:page',
            'sort' => 31,
            'visible' => true,
        ]);

        $tableManager = Menu::query()->create([
            'parentId' => (int) $devTools->id,
            'name' => '数据表管理',
            'path' => '/table-manager',
            'component' => 'dev-tools/table-manager',
            'icon' => 'Database',
            'type' => 'MENU',
            'permissionKey' => 'system:table:page',
            'sort' => 32,
            'visible' => true,
        ]);

        $extensions = Menu::query()->create([
            'parentId' => (int) $devTools->id,
            'name' => '拓展组件',
            'path' => '/extensions',
            'component' => null,
            'icon' => 'Blocks',
            'type' => 'DIRECTORY',
            'permissionKey' => 'system:component:view',
            'sort' => 33,
            'visible' => true,
        ]);

        $this->createExtensionMenu((int) $extensions->id, '日期时间选择器', '/components/date-time-picker', 'extensions/date-time-picker-demo', 'CalendarClock', 'system:component:date-time-picker:page', 34);
        $this->createExtensionMenu((int) $extensions->id, '图标选择器', '/components/icon-picker', 'extensions/icon-picker-demo', 'Palette', 'system:component:icon-picker:page', 35);
        $this->createExtensionMenu((int) $extensions->id, '字典选择器', '/components/dict-select', 'extensions/dict-select-demo', 'ListFilter', 'system:component:dict-select:page', 36);
        $this->createExtensionMenu((int) $extensions->id, '字典显示器', '/components/dict-display', 'extensions/dict-display-demo', 'Tags', 'system:component:dict-display:page', 37);
        $this->createExtensionMenu((int) $extensions->id, '确认弹窗', '/components/confirm-dialog', 'extensions/confirm-dialog-demo', 'AlertTriangle', 'system:component:confirm-dialog:page', 38);
        $this->createExtensionMenu((int) $extensions->id, '密码输入框', '/components/password-input', 'extensions/password-input-demo', 'KeyRound', 'system:component:password-input:page', 39);
        $this->createExtensionMenu((int) $extensions->id, '长文本组件', '/components/long-text', 'extensions/long-text-demo', 'WrapText', 'system:component:long-text:page', 40);
        $this->createExtensionMenu((int) $extensions->id, '富文本框', '/components/rich-text-editor', 'extensions/rich-text-editor-demo', 'FilePenLine', 'system:component:rich-text-editor:page', 41);
        $this->createExtensionMenu((int) $extensions->id, '文件选择器', '/components/file-picker', 'extensions/file-picker-demo', 'FolderSearch', 'system:component:file-picker:page', 42);
        $this->createExtensionMenu((int) $extensions->id, '图片预览', '/components/media-preview', 'extensions/media-preview-demo', 'ImagePlay', 'system:component:media-preview:page', 43);

        $buttonDefs = [
            [$system->id, '修改密码', 'system:auth:change-password'],
            [$userMenu->id, '查询用户', 'system:user:list'],
            [$userMenu->id, '添加用户', 'system:user:add'],
            [$userMenu->id, '编辑用户', 'system:user:edit'],
            [$userMenu->id, '删除用户', 'system:user:delete'],
            [$roleMenu->id, '查询角色', 'system:role:list'],
            [$roleMenu->id, '添加角色', 'system:role:add'],
            [$roleMenu->id, '编辑角色', 'system:role:edit'],
            [$roleMenu->id, '删除角色', 'system:role:delete'],
            [$roleMenu->id, '分配权限', 'system:role:assign'],
            [$menuMenu->id, '查询菜单', 'system:menu:list'],
            [$menuMenu->id, '添加菜单', 'system:menu:add'],
            [$menuMenu->id, '编辑菜单', 'system:menu:edit'],
            [$menuMenu->id, '删除菜单', 'system:menu:delete'],
            [$dictMenu->id, '查询字典', 'system:dict:list'],
            [$dictMenu->id, '添加字典', 'system:dict:add'],
            [$dictMenu->id, '编辑字典', 'system:dict:edit'],
            [$dictMenu->id, '删除字典', 'system:dict:delete'],
            [$operationLog->id, '查询操作日志', 'system:operation-log:list'],
            [$operationLog->id, '删除操作日志', 'system:operation-log:delete'],
            [$loginLog->id, '查询登录日志', 'system:login-log:list'],
            [$loginLog->id, '删除登录日志', 'system:login-log:delete'],
            [$fileMenu->id, '查询文件', 'system:file:list'],
            [$fileMenu->id, '上传文件', 'system:file:upload'],
            [$fileMenu->id, '编辑文件', 'system:file:edit'],
            [$fileMenu->id, '删除文件', 'system:file:delete'],
            [$fileMenu->id, '管理分组', 'system:file:folder'],
            [$tableManager->id, '查询数据表', 'system:table:list'],
            [$tableManager->id, '新建数据表', 'system:table:create'],
            [$tableManager->id, '编辑数据表', 'system:table:edit'],
            [$tableManager->id, '删除数据表', 'system:table:delete'],
        ];

        foreach ($buttonDefs as [$parentId, $name, $permissionKey]) {
            Menu::query()->create([
                'parentId' => (int) $parentId,
                'name' => (string) $name,
                'path' => null,
                'component' => null,
                'icon' => null,
                'type' => 'BUTTON',
                'permissionKey' => (string) $permissionKey,
                'sort' => 100,
                'visible' => false,
            ]);
        }

    }

    private function createExtensionMenu(
        int $parentId,
        string $name,
        string $path,
        string $component,
        string $icon,
        string $permissionKey,
        int $sort
    ): void {
        Menu::query()->create([
            'parentId' => $parentId,
            'name' => $name,
            'path' => $path,
            'component' => $component,
            'icon' => $icon,
            'type' => 'MENU',
            'permissionKey' => $permissionKey,
            'sort' => $sort,
            'visible' => true,
        ]);
    }

    private function seedDictionaries(): void
    {
        DictItem::query()->delete();
        DictType::query()->delete();

        $userStatus = DictType::query()->create([
            'name' => '用户状态',
            'code' => 'user_status',
            'description' => '后台用户状态',
            'status' => true,
            'sort' => 1,
        ]);

        $this->createDictItem((int) $userStatus->id, '启用', 'ACTIVE', 'success', null, true, 1);
        $this->createDictItem((int) $userStatus->id, '停用', 'DISABLED', 'secondary', null, true, 2);

        $menuVisible = DictType::query()->create([
            'name' => '菜单可见',
            'code' => 'menu_visible',
            'description' => '菜单可见状态',
            'status' => true,
            'sort' => 2,
        ]);
        $this->createDictItem((int) $menuVisible->id, '显示', '1', 'success', null, true, 1);
        $this->createDictItem((int) $menuVisible->id, '隐藏', '0', 'secondary', null, true, 2);

        $menuType = DictType::query()->create([
            'name' => '菜单类型',
            'code' => 'menu_type',
            'description' => '目录/菜单/按钮类型',
            'status' => true,
            'sort' => 3,
        ]);
        $this->createDictItem((int) $menuType->id, '目录', 'DIRECTORY', 'info', null, true, 1);
        $this->createDictItem((int) $menuType->id, '菜单', 'MENU', 'success', null, true, 2);
        $this->createDictItem((int) $menuType->id, '按钮', 'BUTTON', 'warning', null, true, 3);

        $logStatus = DictType::query()->create([
            'name' => '日志状态',
            'code' => 'log_status',
            'description' => '登录/操作日志执行结果',
            'status' => true,
            'sort' => 4,
        ]);
        $this->createDictItem((int) $logStatus->id, '成功', 'true', 'success', null, true, 1);
        $this->createDictItem((int) $logStatus->id, '失败', 'false', 'destructive', null, true, 2);

        $fileSource = DictType::query()->create([
            'name' => '文件来源',
            'code' => 'file_source',
            'description' => '文件上传来源',
            'status' => true,
            'sort' => 5,
        ]);
        $this->createDictItem((int) $fileSource->id, '后台上传', 'ADMIN', 'default', null, true, 1);
        $this->createDictItem((int) $fileSource->id, '用户端上传', 'USER', 'warning', null, true, 2);
    }

    private function createDictItem(
        int $dictTypeId,
        string $label,
        string $value,
        ?string $tagType,
        ?string $tagClass,
        bool $status,
        int $sort
    ): void {
        DictItem::query()->create([
            'dictTypeId' => $dictTypeId,
            'label' => $label,
            'value' => $value,
            'tagType' => $tagType,
            'tagClass' => $tagClass,
            'status' => $status,
            'sort' => $sort,
        ]);
    }

    private function seedFileFolders(): void
    {
        FileFolder::query()->delete();

        FileFolder::query()->create([
            'parentId' => null,
            'name' => '演示图片',
            'sort' => 10,
        ]);
    }

    private function seedUsersAndRoles(): void
    {
        DB::table('UserRole')->delete();
        DB::table('RoleMenu')->delete();
        AdminUser::query()->delete();
        Role::query()->delete();

        $superAdminRole = Role::query()->create([
            'name' => '超级管理员',
            'code' => 'SUPER_ADMIN',
            'description' => '拥有全部权限',
        ]);

        $opsRole = Role::query()->create([
            'name' => '运营管理员',
            'code' => 'OPS_ADMIN',
            'description' => '运营权限',
        ]);

        $allMenuIds = Menu::query()->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $superAdminRole->menus()->sync($allMenuIds);

        $opsPermissionKeys = [
            'dashboard:view',
            'system:view',
            'log:view',
            'dev:tools:view',
            'system:auth:change-password',
            'system:user:page',
            'system:user:list',
            'system:user:add',
            'system:user:edit',
            'system:operation-log:page',
            'system:operation-log:list',
            'system:login-log:page',
            'system:login-log:list',
            'system:file:page',
            'system:file:list',
            'system:file:upload',
            'system:file:edit',
            'system:file:delete',
            'system:file:folder',
            'system:component:view',
            'system:component:date-time-picker:page',
            'system:component:icon-picker:page',
            'system:component:dict-select:page',
            'system:component:dict-display:page',
            'system:component:confirm-dialog:page',
            'system:component:password-input:page',
            'system:component:long-text:page',
            'system:component:rich-text-editor:page',
            'system:component:file-picker:page',
            'system:component:media-preview:page',
        ];
        $opsMenuIds = Menu::query()
            ->whereIn('permissionKey', $opsPermissionKeys)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
        $opsRole->menus()->sync($opsMenuIds);

        $admin = AdminUser::query()->create([
            'username' => 'admin',
            'nickname' => '超级管理员',
            'passwordHash' => Hash::make('admin123'),
            'status' => 'ACTIVE',
        ]);

        $ops = AdminUser::query()->create([
            'username' => 'ops',
            'nickname' => '运营',
            'passwordHash' => Hash::make('ops123456'),
            'status' => 'ACTIVE',
        ]);

        $admin->roles()->sync([(int) $superAdminRole->id]);
        $ops->roles()->sync([(int) $opsRole->id]);
    }
}
