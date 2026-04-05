# CodeFabric Agent 指南

## 项目快照
- CodeFabric 是一个面向企业后台场景的全栈管理系统。
- 前端位于 `frontend/`，后端位于 `backend-laravel/`。
- 当前核心能力包括认证、RBAC、字典、文件管理、登录日志、操作日志和数据表管理。
- 后端 API 文档通过 swagger-php attributes 维护，并由 Swagger UI 对外提供。

## 快速开始
- 前端：`cd frontend && npm run dev`
- 后端：`cd backend-laravel && docker compose up -d --build`
- 前端地址：`http://localhost:5173`
- 后端地址：`http://localhost:4000`
- Swagger UI：`http://localhost:4000/api/docs`
- OpenAPI JSON：`http://localhost:4000/api/docs.json`
- 默认演示账号：`admin / admin123`

## 代码地图
- `frontend/src/features/`：业务页面与 feature 级 UI
- `frontend/src/api/`：接口封装与前端侧类型
- `frontend/src/routes/`：TanStack Router 路由文件
- `frontend/src/lib/menu-component-registry.tsx`：后端菜单到前端页面组件的映射
- `frontend/src/stores/auth-store.ts`：登录态、权限、菜单状态
- `backend-laravel/app/Modules/Admin/`：后端业务模块
- `backend-laravel/app/Docs/`：OpenAPI schema 与 spec 根定义
- `backend-laravel/app/Attributes/`：鉴权、日志、事务、锁等自定义 attributes
- `backend-laravel/app/CrossCutting/`：自定义 attributes 的处理器
- `backend-laravel/database/migrations/`：数据库结构定义
- `backend-laravel/database/seeders/`：演示数据、菜单、角色、用户

## 架构摘要
- 后端采用轻量分层结构：
  `Controller -> ApplicationService -> RepositoryInterface -> Repository -> Model`
- Controller 应只负责协议层：请求解析、响应组装、Cookie、状态码。
- ApplicationService 负责业务编排，可结合 `WithTransaction` 与 `WithRedisLock` 使用。
- Repository 接口绑定集中在 `backend-laravel/app/Providers/AppServiceProvider.php`。
- 前端在登录后由菜单驱动：后端返回的菜单决定用户可访问的路由与实际渲染的页面组件。

## 路由与认证
- Laravel 业务路由主要通过 Spatie Route Attributes 注册，不集中写在 `routes/api.php`。
- API 统一前缀为 `/api/admin`，配置在 `backend-laravel/config/route-attributes.php`。
- 后台全局中间件为 `api`、`anno.auth`、`anno.op`。
- 认证方案为 JWT + Cookie。后端写入 `admin_token`，前端通过 `axios` 携带凭据。
- 路由级权限通过 `#[ApiAuth(permission: '...')]` 控制。

## OpenAPI 规则
- OpenAPI 注解分布在 Controller attributes 与 `backend-laravel/app/Docs/` 下的复用 schema 中。
- Auth 模块是当前 Controller 级注解风格的参考实现。
- 修改 OpenAPI 注解后，请执行：
  `cd backend-laravel && docker compose exec app php artisan l5-swagger:generate`
- 生成结果可在以下位置校验：
  `backend-laravel/storage/api-docs/api-docs.json`

## 文档维护规则
- 文档是代码库的一部分，不是任务结束后的可选补充。
- 任何会影响系统认知的改动，都必须在同一任务中同步更新对应文档。
- 常见情况：
  - 新增业务能力或流程：更新 `docs/00-overview.md` 与对应模块文档
  - 架构、路由、认证、部署、协作方式变化：更新 `AGENTS.md`、`docs/architecture.md` 或 `docs/agent-playbook.md`
  - API 契约变化：更新 OpenAPI 注解与相关模块文档
- 在宣告任务完成前，先检查这次改动是否改变了“一个新 agent 应该如何理解或修改系统”。

## 测试维护规则
- 测试是交付的一部分，不是功能完成后的可选补充。
- 只要改动影响业务行为，就必须评估是否需要新增测试或更新现有测试。
- 常见情况：
  - 新接口、新业务规则、新异常分支：默认应补测试
  - 修复 bug：优先补回归测试，避免问题再次出现
  - 重构实现但行为不变：至少确认现有测试仍然覆盖关键行为
- 如果本次改动没有补测试，也应能说明原因，例如纯文档改动、纯样式改动、或当前层没有合理测试入口。
- 在宣告任务完成前，先检查这次改动是否改变了“哪些行为应该被测试保护”。

## 常见改动模式
- 新增或修改 API：
  同步更新 controller annotations、请求校验、application service、前端 `src/api/*`，以及受影响的 OpenAPI schema。
  如果接口行为变化明显，同步补单元测试或 Feature 测试。
- 新增或修改权限：
  同步更新后端菜单/权限定义、`ApiAuth(permission: ...)` 与前端权限判断。
  权限相关改动优先补 Feature 测试。
- 新增或修改后台页面：
  在 `frontend/src/features/` 下增加页面组件，并确保菜单 `component` 或 path fallback 能正确映射。
- 修改数据库结构：
  同步更新 migration、后端 repository/service 逻辑，以及可能受影响的 table management 或前端类型假设。
- 调整文件上传行为：
  保持后端校验、存储配置、前端上传逻辑与 OpenAPI multipart 文档一致。
  涉及上传、删除、导入、权限等高风险行为时补测试。

## 协作说明
- 新 agent 建议先阅读：
  `README.md`
  `docs/00-overview.md`
  `docs/architecture.md`
  `docs/agent-playbook.md`
  `backend-laravel/app/Docs/`
  `backend-laravel/config/route-attributes.php`
  `frontend/src/lib/menu-component-registry.tsx`
- 默认协作模式为三角色：
  - 用户：负责目标、优先级与最终拍板
  - 主 agent：负责分析、拆解、定标准、调度、review、验收
  - 执行 agent：负责在明确范围内实现代码、文档或批量修改
- 推荐工作流：
  - 先由主 agent 理解现状并确定方案
  - 再按模块或批次把实现任务派给执行 agent
  - 每一批实现完成后，由主 agent review 并决定是否继续下一批
  - 关键结论、约束和系统认知应回写到文档
- 如果任务仍然模糊，先让主 agent 分析，不要直接让执行 agent 开始改代码。
- 大任务优先按 `backend-laravel/app/Modules/Admin/` 或 `frontend/src/features/` 的模块边界拆分。
- 涉及文档、接口、权限的改动，优先采用小批次 review。
- 修改 OpenAPI 后，不要只相信注解已经写对，必须重新生成并验证产物。

## 安全注意事项
- 不要把业务逻辑直接写进 Controller。
- 不要只改前端类型或文档而不改真实路由行为，保持实现与文档同步。
- 不要默认业务路由写在 `routes/api.php`，先去对应模块 Controller 检查 route attributes。
- 权限改动要格外谨慎，因为菜单、按钮、后端守卫与前端可见性是联动的。
