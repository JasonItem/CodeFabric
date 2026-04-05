import { createFileRoute, Navigate } from '@tanstack/react-router'
import { Suspense } from 'react'
import { toAppPath } from '@/lib/admin-menu'
import {
  resolveMenuComponentByKey,
  resolveMenuComponentFallbackByPath,
} from '@/lib/menu-component-registry'
import { useAuthStore } from '@/stores/auth-store'

export const Route = createFileRoute('/_authenticated/')({
  component: AuthenticatedHomePage,
})

function AuthenticatedHomePage() {
  const menus = useAuthStore((s) => s.auth.menus)
  const routeMenus = menus.filter((m) => m.type === 'MENU' && m.visible && !!m.path)

  if (routeMenus.length === 0) {
    return <Navigate to='/errors/$error' params={{ error: 'not-found' }} replace />
  }

  const sortedMenus = routeMenus.slice().sort((a, b) => a.sort - b.sort || a.id - b.id)
  const rootMenu =
    sortedMenus.find((menu) => toAppPath(menu.path) === '/') ?? sortedMenus[0]

  const targetPath = toAppPath(rootMenu.path)
  if (targetPath !== '/') {
    return <Navigate to={targetPath} replace />
  }

  const RootPage =
    resolveMenuComponentByKey(rootMenu.component) ??
    resolveMenuComponentFallbackByPath(targetPath)
  if (!RootPage) {
    return <Navigate to='/errors/$error' params={{ error: 'not-found' }} replace />
  }

  return (
    <Suspense fallback={<div className='p-4 text-sm text-muted-foreground'>页面加载中...</div>}>
      <RootPage />
    </Suspense>
  )
}
