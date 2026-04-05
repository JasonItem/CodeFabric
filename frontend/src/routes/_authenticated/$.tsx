import { createFileRoute, Navigate, useLocation } from '@tanstack/react-router'
import { Suspense } from 'react'
import { toAppPath } from '@/lib/admin-menu'
import {
  resolveMenuComponentByKey,
  resolveMenuComponentFallbackByPath,
} from '@/lib/menu-component-registry'
import { useAuthStore } from '@/stores/auth-store'

export const Route = createFileRoute('/_authenticated/$')({
  component: DynamicMenuPage,
})

function normalizePath(pathname: string) {
  if (!pathname) return '/'
  if (pathname === '/') return pathname
  return pathname.endsWith('/') ? pathname.slice(0, -1) : pathname
}

function DynamicMenuPage() {
  const location = useLocation()
  const pathname = normalizePath(location.pathname)
  const menus = useAuthStore((s) => s.auth.menus)

  const menu = menus.find((item) => {
    if (item.type !== 'MENU' || !item.visible || !item.path) return false
    return normalizePath(toAppPath(item.path)) === pathname
  })

  if (!menu) {
    return <Navigate to='/errors/$error' params={{ error: 'not-found' }} replace />
  }

  const PageComponent =
    resolveMenuComponentByKey(menu.component) ??
    resolveMenuComponentFallbackByPath(toAppPath(menu.path))

  if (!PageComponent) {
    return <Navigate to='/errors/$error' params={{ error: 'not-found' }} replace />
  }

  return (
    <Suspense fallback={<div className='p-4 text-sm text-muted-foreground'>页面加载中...</div>}>
      <PageComponent />
    </Suspense>
  )
}
