# Auth 模块

## 模块范围
认证模块覆盖：
- 登录
- 退出登录
- 查询当前会话
- 修改密码

## 后端位置
- `backend-laravel/app/Modules/Admin/Auth/`

## 主要接口
- `POST /api/admin/auth/login`
- `GET /api/admin/auth/me`
- `POST /api/admin/auth/logout`
- `POST /api/admin/auth/change-password`

## 主要行为
- 登录成功后返回一份会话数据，包含 user、roles、permissions 与 menus。
- 后端会在登录成功时写入 `admin_token` Cookie。
- `me` 是前端用来刷新登录态的接口，常用于本地状态缺少用户详情时。
- 修改密码接口带有权限控制，并结合锁与事务能力执行。

## 关键文件
- `backend-laravel/app/Modules/Admin/Auth/Http/Controllers/AuthController.php`
- `backend-laravel/app/Modules/Admin/Auth/Application/Services/AuthApplicationService.php`
- `backend-laravel/app/Modules/Admin/Auth/Domain/Services/AuthContextService.php`
- `frontend/src/api/auth/`
- `frontend/src/stores/auth-store.ts`

## 重要约束
- 登录限流使用 `throttle:admin-login`。
- 认证依赖 JWT 相关配置与 secret。
- 前端依赖后端返回的会话数据结构保持稳定。

## 修改这个模块时
- 检查 Cookie 行为
- 检查前端跳转登录页逻辑
- 检查 `auth-store` 兼容性
- 同步更新 OpenAPI 并重新生成文档
