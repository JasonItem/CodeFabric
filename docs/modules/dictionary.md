# Dictionary 模块

## 模块范围
字典管理提供：
- 字典类型管理
- 字典项管理
- 按字典 code 查询 options

## 后端位置
- `backend-laravel/app/Modules/Admin/Dictionary/`

## 前端使用位置
字典数据会驱动一些可复用的前端控件，例如 select 与展示组件。

关键位置：
- `frontend/src/api/dictionary/`
- `frontend/src/components/dict-select.tsx`
- `frontend/src/components/dict-display.tsx`
- `frontend/src/hooks/use-dict-options.ts`
- `frontend/src/features/rbac/dictionaries-page.tsx`

## 主要接口
- `GET/POST/PUT/DELETE /api/admin/dictionaries/types`
- `GET/POST /api/admin/dictionaries/types/{typeId}/items`
- `PUT/DELETE /api/admin/dictionaries/items/{id}`
- `GET /api/admin/dictionaries/options/{code}`

## 主要行为
- type 用来定义一类逻辑字典。
- item 用来定义某个 type 下的具体值。
- `options/{code}` 是一个高复用接口，前端会用它动态拉取下拉选项。

## 重要约束
- type code 应保持稳定，因为前端组件可能直接依赖它。
- 同一个 type 下的 item value 需要唯一。
- 这个模块的变更常常会通过共享字典组件间接影响多个页面。

## 修改这个模块时
- 检查前端是否有写死的 dictionary code
- 确认 options payload 结构保持稳定
- OpenAPI 与模块文档一起更新
