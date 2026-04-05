import { type AdminMenu } from '@/stores/auth-store'

function normalizePath(pathname: string) {
  if (!pathname) return '/'
  if (pathname === '/') return pathname
  return pathname.endsWith('/') ? pathname.slice(0, -1) : pathname
}

function escapeRegex(input: string) {
  return input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

export function toAppPath(path: string | null) {
  if (!path) return '/'
  if (path === '/admin') return '/'

  const withoutAdmin = path.startsWith('/admin') ? path.replace('/admin', '') : path
  const normalized = withoutAdmin.startsWith('/') ? withoutAdmin : `/${withoutAdmin}`
  return normalizePath(normalized || '/')
}

function createPathMatcher(path: string) {
  const normalized = normalizePath(path)
  if (normalized === '/') {
    return (pathname: string) => normalizePath(pathname) === '/'
  }

  const dynamicPattern = escapeRegex(normalized).replace(/:([^/]+)/g, '[^/]+')
  const regex = new RegExp(`^${dynamicPattern}(?:/.*)?$`)
  return (pathname: string) => regex.test(normalizePath(pathname))
}

export function canAccessRoute(pathname: string, menus: AdminMenu[]) {
  const normalized = normalizePath(pathname)
  if (normalized.startsWith('/errors/')) return true

  const routeMenus = menus.filter(
    (menu) => menu.type === 'MENU' && menu.visible && !!menu.path
  )

  if (routeMenus.length === 0) return true
  if (normalized === '/') return true

  return routeMenus.some((menu) => {
    const appPath = toAppPath(menu.path)
    return createPathMatcher(appPath)(normalized)
  })
}
