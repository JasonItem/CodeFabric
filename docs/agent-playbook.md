# Agent 协作手册

## 目的
这份手册说明 agent 应该如何在 CodeFabric 仓库中协作，重点是安全改动、降低意外、并让文档始终与实现保持同步。

## 工作原则
- 优先选择小批次、可 review 的改动，而不是大范围重构。
- 把文档视为任务本身的一部分，而不是收尾时的可选清理。
- 不要只改某一层行为，却让契约文档或依赖层停留在旧状态。
- 除非有明确决策，否则尽量保持现有架构风格。

## 文档维护规则
任何会改变系统理解方式的改动，都必须在同一任务中更新对应文档。

执行前可用这份检查清单：
- 这次任务是否新增或删除了业务能力？
- 是否修改了 API 契约、权限模型、路由形态、启动流程或部署流程？
- 是否改变了代码位置或页面解析方式？

如果答案是“是”，就在任务结束前同步更新对应文档。

## 测试维护规则
任何会改变业务行为的改动，都必须在同一任务中评估测试影响。

执行前可用这份检查清单：
- 这次任务是否新增了接口、规则、异常分支或权限行为？
- 这次任务是否修复了一个真实 bug？
- 这次任务是否可能让现有测试失效，或让旧测试不再覆盖关键路径？

如果答案是“是”，就需要：
- 新增测试，或
- 更新现有测试，或
- 明确说明为什么这次不适合补测试

默认优先级建议：
- 新业务规则：补单元测试
- 关键接口链路、权限、上传、删除、鉴权：补 Feature 测试
- bug 修复：补回归测试

## 零上下文时先读哪些文件
对于没有上下文的工作，建议按这个顺序阅读：
1. `AGENTS.md`
2. `docs/00-overview.md`
3. `docs/architecture.md`
4. `backend-laravel/config/route-attributes.php`
5. `frontend/src/lib/menu-component-registry.tsx`
6. 你这次要修改的业务模块

## 默认协作模式
CodeFabric 推荐使用“三角色协作”：
- 用户：负责目标、优先级、约束和最终决策
- 主 agent：负责理解项目、制定方案、拆任务、调度执行 agent、review 与验收
- 执行 agent：负责在清晰边界内落地实现，例如写代码、补文档、批量修改同类接口

这个模式下，主 agent 不是单纯的实现者，而是整个任务的技术负责人。

## 什么时候该找谁
### 优先找主 agent 的情况
- 需求仍然模糊
- 任务跨多个模块
- 涉及认证、权限、数据库结构、删除数据、重构
- 你需要先分析风险、定方案或做 review

### 适合派给执行 agent 的情况
- 已经明确范围和标准
- 任务模式比较固定，例如补 OpenAPI、补文档、补 CRUD、按现有风格补测试
- 可以按模块或批次稳定拆分

## 推荐工作流
推荐默认采用以下顺序：
1. 用户提出目标或问题。
2. 主 agent 先分析现状、风险和可行方案。
3. 主 agent 明确约束、验收标准和批次边界。
4. 主 agent 将单批次实现任务派给执行 agent。
5. 执行 agent 完成当前批次。
6. 主 agent review 结果，修正方向，并决定是否继续下一批。
7. 关键结论与系统认知回写到文档。

如果一个新主 agent 接手当前仓库，默认应继承这套工作方式，而不是跳过分析直接大范围改动。

## 常见任务操作手册
### 新增或修改 API 接口
通常会涉及：
- controller route attributes
- request validation
- application service
- 必要时的 repository 或 model
- 前端 `src/api/*`
- Controller 上的 OpenAPI 注解与 `backend-laravel/app/Docs/`
- 如果行为变化明显，还要更新模块文档

验收时建议检查：
- route path 与 method 是否正确
- auth / permission 行为是否正确
- OpenAPI 是否重新生成
- 如果前端依赖该接口，类型与页面行为是否一致
- 是否需要补对应的单元测试或 Feature 测试

### 修改认证或权限逻辑
通常会涉及：
- `ApiAuth` 的使用方式
- auth 或 RBAC application service
- 菜单/权限定义或种子数据
- 前端 auth store 与权限 gate
- 描述认证或协作假设的相关文档

验收时建议检查：
- 未登录行为
- 无权限行为
- 前端路由与菜单可见性是否仍与后端规则一致
- 是否补了关键权限链路的 Feature 测试

### 新增或修改后台页面
通常会涉及：
- `frontend/src/features/`
- 菜单配置或菜单 seed 数据
- 菜单到组件的映射
- 必要时的前端路由或 fallback 逻辑

验收时建议检查：
- 页面是否能从后端菜单配置正确解析
- 预期权限下的路由访问是否正常

### 修改数据库结构或持久化逻辑
通常会涉及：
- migrations
- repositories
- application services
- 前端类型假设
- 如果对外行为有变化，更新文档

验收时建议检查：
- migration 是否能干净执行
- 如受影响，seed 行为是否正常
- API payload 是否仍与文档 schema 一致
- 原有测试是否仍有效，是否需要补 repository / service / feature 测试

## OpenAPI 操作手册
### 事实来源
OpenAPI 在代码中维护：
- 复用 schema：`backend-laravel/app/Docs/`
- 接口级文档：Controller 上的 `OA\*` attributes

### 编写规则
- 路径与方法必须与真实接口一致
- 需要登录的接口必须写 `security`
- 需要登录的接口必须补 `401`
- 存在权限校验的接口要补 `403`
- 请求体必须反映真实的 request validation 规则
- 上传/导入类接口必须使用 multipart 文档
- 优先复用 schema，避免写大段内联 payload

### 验证方式
修改 OpenAPI 后执行：
```bash
cd backend-laravel
docker compose exec app php artisan l5-swagger:generate
```

然后确认：
- `backend-laravel/storage/api-docs/api-docs.json` 已生成
- 变更过的路径确实出现在生成文件中

## 协作模式
### 好的拆分方式
优先按模块或清晰分离的关注点拆分：
- 一个 agent 负责文档
- 一个 agent 负责前端
- 一个 agent 负责后端模块

尽量避免多个 agent 改同一组文件。

### 好的 review 边界
适合在这些粒度上做 review：
- 一个模块
- 一组 API
- 一次 migration 及其依赖行为

尽量不要把多个无关模块打包到同一次 review 里。

## 应避免的事情
- 把业务逻辑直接写进 Controller
- 默认认为 `routes/api.php` 就是全部业务路由
- 只改前端 API 类型，却不核对后端实际输出
- 改了后端行为，却不检查 OpenAPI
- 改了菜单或权限行为，却不检查前端路由和页面访问
- 架构或行为变了，却让文档继续过期

## 完成前检查
- 代码改动是否符合项目分层方式
- 受影响文档是否已更新
- 受影响测试是否已新增、更新或明确说明
- 如果 API 有变化，OpenAPI 是否已重新生成
- 相关路由或页面是否仍能正常解析
- 认证与权限行为是否仍合理
- 一个零上下文 agent 是否能通过文档继续接手
