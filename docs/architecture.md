# 架构说明

## 目的
这份文档说明 CodeFabric 的整体结构，以及请求如何在系统中流转。它面向需要在没有历史上下文的情况下安全改动项目的工程师或 agent。

## 高层形态
CodeFabric 是一个前后端分离的后台系统：
- `frontend/`：React 后台前端
- `backend-laravel/`：Laravel API 与业务逻辑

系统整体由后端能力驱动。认证、权限、菜单结构和大部分业务数据都来自后端，前端更像是一个动态后台外壳加一组 feature 页面。

## 后端架构
### 模块结构
后端主业务代码位于：
- `backend-laravel/app/Modules/Admin/`

每个业务模块大致遵循以下轻量分层：
- `Http/Controllers/`：协议层
- `Http/Requests/`：请求校验
- `Application/Services/`：业务编排
- `Domain/Contracts/`：Repository 接口
- `Infrastructure/Persistence/Repositories/`：Repository 实现

相关支撑目录：
- `backend-laravel/app/Models/`：Eloquent 模型
- `backend-laravel/app/Docs/`：OpenAPI schema 与 spec 根定义
- `backend-laravel/app/Attributes/`：自定义 attributes
- `backend-laravel/app/CrossCutting/`：自定义 attributes 的处理器
- `backend-laravel/app/Shared/`：共享基类与异常

### 请求流转
典型的后端请求链路如下：
1. 路由命中某个 Controller 方法。
2. `anno.auth` 中间件读取 `ApiAuth` attribute，执行登录与权限检查。
3. `anno.op` 中间件读取 `OperationLog` attribute，在需要时记录操作日志。
4. Controller 通过 `FormRequest` 或原始请求规则完成输入校验。
5. Controller 将业务行为委托给 application service。
6. Application service 协调 repositories、models 与领域行为。
7. 统一响应工具返回 `{ code, message, data }`。

### 路由注册
业务路由并不主要写在 `routes/api.php`，而是通过 Spatie Route Attributes 从 Controller attributes 自动发现。

关键文件：
- `backend-laravel/config/route-attributes.php`
- `backend-laravel/app/Modules/Admin/*/Http/Controllers/*.php`

需要记住的事实：
- API 前缀：`/api/admin`
- 全局中间件：`api`、`anno.auth`、`anno.op`
- 路由声明直接挂在 Controller 方法上，常见为 `Get`、`Post`、`Put`、`Delete`

### 横切能力
项目使用自定义 attributes 来让 Controller 与 Service 保持整洁。

主要 attributes：
- `ApiAuth`：登录与权限控制
- `OperationLog`：操作日志采集
- `WithTransaction`：为 service 方法加事务
- `WithRedisLock`：为敏感操作加 Redis 锁

关键文件：
- `backend-laravel/app/Attributes/`
- `backend-laravel/app/CrossCutting/`
- `backend-laravel/app/Shared/Application/ApplicationService.php`

### 认证与权限模型
后端返回的会话数据通常包括：
- 当前用户
- 角色
- 权限 key
- 菜单树

权限通过 `ApiAuth(permission: '...')` 执行。菜单与按钮权限在数据库中种子初始化，并作为登录态的一部分返回给前端。

关键位置：
- `backend-laravel/app/Modules/Admin/Auth/`
- `backend-laravel/database/seeders/Admin/AdminRbacSeeder.php`

## 前端架构
### 主要目录
- `frontend/src/routes/`：TanStack Router 路由
- `frontend/src/features/`：业务页面
- `frontend/src/api/`：请求封装与类型
- `frontend/src/components/`：复用 UI 与组件
- `frontend/src/stores/`：Zustand 状态
- `frontend/src/lib/`：路由、菜单解析与工具函数

### 启动链路
1. React 应用从 `frontend/src/main.tsx` 启动。
2. 创建 router 与 query client。
3. 受保护路由检查本地登录状态。
4. 如有需要，前端调用 `/api/admin/auth/me`。
5. 后端会话数据写入 `auth-store`。
6. 菜单与权限共同决定路由访问与 UI 可见性。

### 菜单驱动渲染
CodeFabric 的一个核心设计是：前端渲染会受到后端菜单数据直接影响。

关键文件：
- `frontend/src/stores/auth-store.ts`
- `frontend/src/lib/admin-menu.ts`
- `frontend/src/lib/menu-component-registry.tsx`

它带来的影响包括：
- 路由可见性由后端驱动
- 默认首页可由后端菜单决定
- 菜单中的 `component` 字段会映射到真实 React 页面模块
- 当 `component` 为空时，会走 path fallback 映射

这意味着菜单改动通常会同时影响后端数据与前端渲染行为。

## 数据与持久化
### 核心数据模型
主要表结构可以按以下几组理解：
- RBAC：users、roles、menus、user-role、role-menu
- dictionary types 与 items
- login logs
- operation logs
- file folders 与 stored files

定义位置：
- `backend-laravel/database/migrations/`

种子位置：
- `backend-laravel/database/seeders/`

### 文件存储
文件通过 Laravel filesystem 配置进行存储。

关键位置：
- `backend-laravel/config/filesystems.php`
- `backend-laravel/app/Modules/Admin/File/`

在本地 Docker 开发环境中，上传文件通过 Laravel 的 public storage 映射进行访问。

## API 契约
### 响应格式
大部分后端接口返回如下结构：
```json
{
  "code": 200,
  "message": "ok",
  "data": {}
}
```

项目在响应体和 HTTP 状态中都使用 HTTP 风格的数值状态码。

关键文件：
- `backend-laravel/app/Support/ApiResponse.php`
- `backend-laravel/app/Enums/ApiCode.php`

### OpenAPI 归属
OpenAPI 在代码中维护。

关键位置：
- `backend-laravel/app/Docs/OpenApiSpec.php`
- `backend-laravel/app/Docs/*.php`
- 各 Controller 上的 `OA\*` attributes

生成命令：
```bash
cd backend-laravel
docker compose exec app php artisan l5-swagger:generate
```

生成产物：
- `backend-laravel/storage/api-docs/api-docs.json`

## 本地运行架构
### 后端环境
本地后端默认通过 Docker Compose 运行：
- Laravel app
- MySQL
- Redis

主配置文件：
- `backend-laravel/docker-compose.yml`

### 前端环境
前端默认在 Docker 外通过本地 Vite dev server 启动：
```bash
cd frontend
npm run dev
```

## 改动影响指引
### 如果你修改了 API 行为
通常需要同时检查：
- 后端 controller 或 request validation
- application service
- 前端 `src/api/*`
- OpenAPI 文档
- 必要时更新模块文档

### 如果你修改了权限
通常需要同时检查：
- 后端菜单或权限定义
- `ApiAuth(permission: ...)`
- 前端权限判断
- 菜单渲染行为
- 相关文档

### 如果你修改了页面映射
通常需要同时检查：
- 后端菜单 seed 或菜单记录
- `frontend/src/lib/menu-component-registry.tsx`
- 路由访问预期

### 如果你修改了数据库结构
通常需要同时检查：
- migrations
- repositories 与 services
- 前端暴露出的类型
- table management 的相关假设
- 行为有变化时同步更新文档

## 建议阅读顺序
1. `AGENTS.md`
2. `docs/00-overview.md`
3. `docs/architecture.md`
4. `docs/agent-playbook.md`
5. `docs/modules/` 下的模块文档
