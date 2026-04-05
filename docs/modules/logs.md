# Log 模块

## 模块范围
CodeFabric 当前有两类日志模块：
- 登录日志
- 操作日志

## 后端位置
- `backend-laravel/app/Modules/Admin/LoginLog/`
- `backend-laravel/app/Modules/Admin/OperationLog/`

## 前端位置
- `frontend/src/features/rbac/login-logs-page.tsx`
- `frontend/src/features/rbac/operation-logs-page.tsx`

## 主要接口
### 登录日志
- `GET /api/admin/login-logs`
- `DELETE /api/admin/login-logs`

### 操作日志
- `GET /api/admin/operation-logs`
- `GET /api/admin/operation-logs/{id}`
- `DELETE /api/admin/operation-logs`

## 主要行为
- 登录日志记录登录相关的成功与失败事件。
- 操作日志通过 `OperationLog` attribute 与对应中间件自动生成。
- 清理接口支持按选中 ID 删除，也支持在不传 ID 时清空全部。

## 重要约束
- 操作日志采集属于横切能力，Controller 变更时不能无意中把它破坏掉。
- 敏感请求字段在持久化前会做脱敏处理。
- 列表接口是分页接口，并且和前端筛选 UI 强绑定。

## 修改这些模块时
- 保持 query 参数名与前端页面一致
- 如果调整操作日志行为，检查 `backend-laravel/app/CrossCutting/OperationLogHandler.php`
- OpenAPI 与模块文档一起更新
