import { type ElementType } from 'react'
import { Circle } from 'lucide-react'
import { useLayout } from '@/context/layout-provider'
import { toAppPath } from '@/lib/admin-menu'
import { useAuthStore } from '@/stores/auth-store'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'
import { AppTitle } from './app-title'
import { NavGroup } from './nav-group'
import { NavUser } from './nav-user'
import {
  type NavCollapsible,
  type NavGroup as NavGroupType,
  type NavItem,
  type NavLink,
} from './types'
import { LucideIconByName } from '@/components/icon-picker'

const iconComponentCache = new Map<string, ElementType>()

function getMenuIconComponent(iconName?: string | null): ElementType {
  if (!iconName) return Circle

  if (iconComponentCache.has(iconName)) {
    return iconComponentCache.get(iconName)!
  }

  const DynamicIcon = ((props: { className?: string }) => (
    <LucideIconByName
      name={iconName}
      className={props.className}
      fallback={<Circle className={props.className} />}
    />
  )) as ElementType

  iconComponentCache.set(iconName, DynamicIcon)
  return DynamicIcon
}

type StoreMenus = ReturnType<typeof useAuthStore.getState>['auth']['menus']
type MenuTreeNode = StoreMenus[number] & { children: MenuTreeNode[] }

function sortMenus(list: MenuTreeNode[]) {
  return list.sort((a, b) => a.sort - b.sort || a.id - b.id)
}

function mapTreeToNavItem(menu: MenuTreeNode): NavItem | null {
  const children = sortMenus(menu.children)
    .map((child) => mapTreeToNavItem(child))
    .filter((item): item is NavItem => Boolean(item))

  const icon = getMenuIconComponent(menu.icon)

  if (menu.type === 'DIRECTORY') {
    return {
      title: menu.name,
      icon,
      items: children,
    } satisfies NavCollapsible
  }

  if (menu.type === 'MENU') {
    if (children.length > 0) {
      return {
        title: menu.name,
        icon,
        items: children,
      } satisfies NavCollapsible
    }

    if (!menu.path) return null

    return {
      title: menu.name,
      url: toAppPath(menu.path),
      icon,
    } satisfies NavLink
  }

  return null
}

function buildNavGroups(menus: ReturnType<typeof useAuthStore.getState>['auth']['menus']) {
  const visibleMenus = menus.filter((m) => m.visible && m.type !== 'BUTTON')
  const map = new Map<number, MenuTreeNode>(
    visibleMenus.map((m) => [m.id, { ...m, children: [] as MenuTreeNode[] }])
  )
  const roots: MenuTreeNode[] = []

  for (const menu of map.values()) {
    if (menu.parentId && map.has(menu.parentId)) {
      map.get(menu.parentId)!.children.push(menu)
    } else {
      roots.push(menu)
    }
  }

  const systemItems: NavGroupType['items'] = sortMenus(roots)
    .map((root) => mapTreeToNavItem(root))
    .filter((item): item is NavItem => Boolean(item))

  return systemItems.length > 0 ? [{ items: systemItems }] : []
}

export function AppSidebar() {
  const { collapsible, variant } = useLayout()
  const { auth } = useAuthStore()
  const navGroups = buildNavGroups(auth.menus)

  const user = auth.user
    ? {
        name: auth.user.nickname,
        email: auth.user.username,
        avatar: '/avatars/shadcn.jpg',
      }
    : {
        name: '管理员',
        email: 'admin',
        avatar: '/avatars/shadcn.jpg',
      }

  return (
    <Sidebar collapsible={collapsible} variant={variant}>
      <SidebarHeader>
        <AppTitle />
      </SidebarHeader>
      <SidebarContent>
        {navGroups.map((props, index) => (
          <NavGroup key={`group-${index}`} {...props} />
        ))}
      </SidebarContent>
      <SidebarFooter>
        <NavUser user={user} />
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
