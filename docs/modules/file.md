# File 模块

## 模块范围
文件管理覆盖：
- 文件夹管理
- 文件上传
- 带筛选的文件列表
- 文件重命名与移动
- 单个与批量删除

## 后端位置
- `backend-laravel/app/Modules/Admin/File/`

## 前端位置
- `frontend/src/features/rbac/files-page.tsx`
- `frontend/src/api/file/`

## 主要接口
- `GET/POST/PUT/DELETE /api/admin/files/folders`
- `GET /api/admin/files`
- `POST /api/admin/files/upload`
- `PUT/DELETE /api/admin/files/{id}`
- `POST /api/admin/files/batch-delete`

## 主要行为
- 文件可以归属某个文件夹，也可以位于根目录。
- 上传文件通过 Laravel filesystem 的 public storage 保存。
- 前端会把文件 URL 归一化为可访问的绝对地址。
- 删除文件夹时，通常需要处理已有文件迁移到目标文件夹或根目录的规则。

## 重要约束
- 上传校验基于扩展名与大小限制。
- 上传/导入类文档必须使用 multipart 语义。
- 文件与文件夹操作都属于权限敏感行为。

## 修改这个模块时
- 保持校验、存储配置与前端上传行为一致
- 仔细检查删除文件夹时的迁移规则
- 如果请求结构有变化，同步更新 OpenAPI multipart 文档
