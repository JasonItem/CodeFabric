# RBAC 模块

## 模块范围
CodeFabric 的 RBAC 包括：
- 用户
- 角色
- 菜单
- 按钮级权限 key

## 后端位置
- `backend-laravel/app/Modules/Admin/User/`
- `backend-laravel/app/Modules/Admin/Role/`
- `backend-laravel/app/Modules/Admin/Menu/`

## 前端位置
- `frontend/src/features/rbac/users-page.tsx`
- `frontend/src/features/rbac/roles-page.tsx`
- `frontend/src/features/rbac/menus-page.tsx`
- `frontend/src/features/rbac/permission-gate.tsx`

## 主要接口
- `GET/POST/PUT/DELETE /api/admin/users`
- `GET/POST/PUT/DELETE /api/admin/roles`
- `POST /api/admin/roles/{id}/permissions`
- `GET/POST/PUT/DELETE /api/admin/menus`

## 主要行为
- 用户被分配角色。
- 角色被分配菜单。
- 菜单中承载权限 key。
- 按钮级权限也存放在菜单记录中，通常表现为 `type = BUTTON`。
- 前端根据后端返回的权限决定某些操作是否可见。

## 与种子数据的强耦合
RBAC 行为与种子里的菜单和权限定义强相关：
- `backend-laravel/database/seeders/Admin/AdminRbacSeeder.php`

如果你修改了权限 key 或菜单语义，需要同时检查：
- 后端 `ApiAuth(permission: ...)`
- 前端权限判断
- 种子中的菜单与按钮定义

## 菜单驱动 UI 说明
菜单不只是导航，它也会影响路由访问与页面组件渲染。后端菜单记录与前端菜单解析逻辑必须保持一致。

## 修改这个模块时
- 保持后端与前端使用的 permission key 一致
- 检查角色分配菜单的行为
- 检查菜单 component 与 path 的映射
- OpenAPI 与模块文档一起更新
