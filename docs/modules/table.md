# Table 模块

## 模块范围
数据表管理提供数据库结构探查与受控 SQL 操作能力。

主要功能包括：
- 表列表
- 字段
- 索引
- 外键
- 查看建表 SQL
- 单表导出
- 全量导出
- SQL 导入
- 使用 SQL 建表
- 使用 SQL 改表
- 删除表
- 清空表

## 后端位置
- `backend-laravel/app/Modules/Admin/Table/`

## 前端位置
- `frontend/src/features/dev-tools/table-manager-page.tsx`
- `frontend/src/api/table/`

## 主要接口
- `GET /api/admin/tables`
- `GET /api/admin/tables/{tableName}/columns`
- `GET /api/admin/tables/{tableName}/create-sql`
- `GET /api/admin/tables/{tableName}/indexes`
- `GET /api/admin/tables/{tableName}/foreign-keys`
- `GET /api/admin/tables/{tableName}/export`
- `GET /api/admin/tables/export-all`
- `POST /api/admin/tables/import`
- `POST /api/admin/tables/create`
- `POST /api/admin/tables/alter`
- `DELETE /api/admin/tables/{tableName}`
- `POST /api/admin/tables/{tableName}/truncate`

## 主要行为
- 所有操作执行前都会先校验表名。
- `import` 只接受受控范围内的 SQL 语句。
- `create` 与 `alter` 接口通常只接受单一类型的 SQL 语句。
- 导出接口返回的是 JSON 中的 SQL 内容，而不是直接流式下载文件。

## 重要约束
- 这个模块影响很大，因为它能直接修改真实 schema 与数据。
- 请求文档必须清楚描述 multipart 导入与 SQL 请求体格式。
- 校验和错误语义很重要，因为前端工具页会直接依赖这些反馈。

## 修改这个模块时
- 同时检查后端 table service 逻辑与前端 table manager 预期
- 除非有明确设计调整，否则保持现有 identifier 校验方式
- OpenAPI 与模块文档一起更新
