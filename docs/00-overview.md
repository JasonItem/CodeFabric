# CodeFabric 总览

## 这个项目是什么
CodeFabric 是一个面向企业后台场景的全栈管理系统，更接近可复用的管理系统底座，而不是一次性的演示项目。当前代码库已经包含可工作的权限模型、后台 UI 外壳、审计日志、文件管理、字典管理和数据库表工具。

仓库主要由两个应用组成：
- `frontend/`：React 后台前端
- `backend-laravel/`：Laravel 后端 API

## 核心能力
- 认证：登录、退出、当前会话查询、修改密码
- RBAC：用户、角色、菜单、按钮级权限
- 菜单驱动后台 UI：后端菜单决定可见路由与页面渲染
- 字典管理：字典类型与字典项
- 文件管理：文件夹、上传、移动、重命名、删除、筛选
- 审计能力：
  - 登录日志
  - 操作日志
- 数据表管理：
  - 表列表
  - 字段
  - 索引
  - 外键
  - 建表 SQL
  - SQL 导入/导出
  - 建表、改表、删表、清空表

## 技术栈
### 前端
- React 19
- TypeScript
- Vite
- TanStack Router
- TanStack Query
- shadcn/ui
- Zustand
- Axios

### 后端
- Laravel 13
- PHP 8.4 Docker 镜像
- MySQL
- Redis
- `spatie/laravel-route-attributes`
- `darkaonline/l5-swagger`
- `firebase/php-jwt`

## 仓库结构
```text
.
├─ AGENTS.md
├─ docs/
├─ frontend/
│  ├─ src/api/
│  ├─ src/features/
│  ├─ src/routes/
│  └─ src/stores/
└─ backend-laravel/
   ├─ app/Modules/Admin/
   ├─ app/Docs/
   ├─ app/Attributes/
   ├─ app/CrossCutting/
   ├─ config/
   ├─ database/migrations/
   ├─ database/seeders/
   └─ docker-compose.yml
```

## 系统如何运转
### 前端链路
1. 用户在 React 应用中登录。
2. 后端返回会话数据，包括 `user`、`roles`、`permissions`、`menus`。
3. 前端将这份数据存入 `frontend/src/stores/auth-store.ts`。
4. 路由访问权限根据后端返回的菜单进行判断。
5. 菜单项通过 `frontend/src/lib/menu-component-registry.tsx` 解析到 React 页面组件。

### 后端链路
1. 路由从 `backend-laravel/app/Modules/Admin/` 下的 Controller attributes 自动发现。
2. 路由统一注册到 `/api/admin`。
3. `anno.auth` 负责登录与权限检查。
4. `anno.op` 负责为标记了操作日志的动作记录日志。
5. Controller 将业务委托给 application service。
6. Application service 编排 repository 与领域行为。

## 关键架构决策
### 1. 后端路由采用 attribute 注册
大部分业务路由不写在 `routes/api.php`，而是通过 `spatie/laravel-route-attributes` 从 Controller attributes 中注册。统一前缀与中间件配置在 `backend-laravel/config/route-attributes.php`。

### 2. Controller 保持薄层
Controller 主要负责协议层。如果一个改动涉及数据校验、业务编排、加锁或事务处理，它通常应该放在更下层，而不是直接写在 Controller 里。

### 3. 认证与权限由后端驱动
权限通过 `#[ApiAuth(permission: ...)]` 在后端强制执行；前端则使用后端返回的权限和菜单数据控制页面显示与路由访问。

### 4. UI 是菜单驱动的
这是本项目最重要的概念之一。页面不只是由前端路由定义，也由后端菜单数据激活。修改菜单或页面映射时，要同时检查后端菜单定义与前端菜单解析逻辑。

### 5. OpenAPI 在代码中维护
Swagger 文档由 Controller attributes 和 `backend-laravel/app/Docs/` 下的复用 schema 生成。修改注解后，需要重新生成并校验文档。

## 本地运行
### 后端
在仓库根目录执行：
```bash
cd backend-laravel
docker compose up -d --build
```

关键地址：
- API：`http://localhost:4000`
- Swagger UI：`http://localhost:4000/api/docs`
- OpenAPI JSON：`http://localhost:4000/api/docs.json`

### 前端
在仓库根目录执行：
```bash
cd frontend
npm run dev
```

前端地址：
- `http://localhost:5173`

### 演示账号
- 用户名：`admin`
- 密码：`admin123`

## 数据与种子状态
seeders 会直接初始化一个可用的后台环境，内容包括：
- 默认菜单
- 默认字典数据
- 默认文件夹种子数据
- 演示用户与角色

主要入口：
- `backend-laravel/database/seeders/DatabaseSeeder.php`

## 零上下文时从哪里开始看
如果你对项目完全没有上下文，建议按下面顺序阅读：
1. `AGENTS.md`
2. `README.md`
3. `docs/00-overview.md`
4. `backend-laravel/config/route-attributes.php`
5. `backend-laravel/app/Docs/`
6. `backend-laravel/app/Modules/Admin/`
7. `frontend/src/lib/menu-component-registry.tsx`
8. `frontend/src/stores/auth-store.ts`

## 后续建议阅读
这份总览负责提供大地图。更适合继续深入的文档有：
- `docs/architecture.md`：更细的前后端结构与请求链路
- `docs/agent-playbook.md`：agent 应如何安全修改代码库
- `docs/modules/*.md`：按业务模块拆分的说明文档
