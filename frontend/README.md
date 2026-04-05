# CodeFabric Admin

CodeFabric 后台管理端（Vite + React + TanStack Router + shadcn/ui）。

## 启动

```bash
npm install
npm run dev
```

默认开发地址：`http://localhost:5173`

## 构建

```bash
npm run build
npm run preview
```

## 说明

- 后台权限模型：RBAC（用户-角色-菜单/权限）
- 菜单由后端下发，并基于 `menu.component` 动态加载页面
- API 请求统一走 `src/api/http.ts`
- 详细架构与二开说明见 `../md/后台`
