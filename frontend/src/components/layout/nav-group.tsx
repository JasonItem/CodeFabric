import { type ReactNode } from 'react'
import { Link, useLocation } from '@tanstack/react-router'
import { ChevronRight } from 'lucide-react'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  useSidebar,
} from '@/components/ui/sidebar'
import { Badge } from '../ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../ui/dropdown-menu'
import {
  type NavCollapsible,
  type NavItem,
  type NavLink,
  type NavGroup as NavGroupProps,
} from './types'

export function NavGroup({ title, items }: NavGroupProps) {
  const { state, isMobile } = useSidebar()
  const href = useLocation({ select: (location) => location.href })
  return (
    <SidebarGroup>
      {title ? <SidebarGroupLabel>{title}</SidebarGroupLabel> : null}
      <SidebarMenu>
        {items.map((item, index) => {
          const key = `${item.title}-${item.url ?? 'group'}-${index}`

          if (!item.items) return <SidebarMenuLink key={key} item={item} href={href} />

          if (state === 'collapsed' && !isMobile) {
            return <SidebarMenuCollapsedDropdown key={key} item={item} href={href} />
          }

          return <SidebarMenuCollapsible key={key} item={item} href={href} />
        })}
      </SidebarMenu>
    </SidebarGroup>
  )
}

function NavBadge({ children }: { children: ReactNode }) {
  return <Badge className='rounded-full px-1 py-0 text-xs'>{children}</Badge>
}

function SidebarMenuLink({ item, href }: { item: NavLink; href: string }) {
  const { setOpenMobile } = useSidebar()
  return (
    <SidebarMenuItem>
      <SidebarMenuButton
        asChild
        isActive={checkIsActive(href, item)}
        tooltip={item.title}
      >
        <Link to={item.url} onClick={() => setOpenMobile(false)}>
          {item.icon && <item.icon />}
          <span>{item.title}</span>
          {item.badge && <NavBadge>{item.badge}</NavBadge>}
        </Link>
      </SidebarMenuButton>
    </SidebarMenuItem>
  )
}

function SidebarMenuCollapsible({
  item,
  href,
}: {
  item: NavCollapsible
  href: string
}) {
  return <SidebarCollapsibleNode item={item} href={href} level={0} />
}

function SidebarCollapsibleNode({
  item,
  href,
  level,
}: {
  item: NavCollapsible
  href: string
  level: number
}) {
  const { setOpenMobile } = useSidebar()
  const childKeyPrefix = `${item.title}-${level}`

  const trigger = level > 0 ? (
    <SidebarMenuSubButton>
      {item.icon && <item.icon />}
      <span>{item.title}</span>
      {item.badge && <NavBadge>{item.badge}</NavBadge>}
      <ChevronRight className='ms-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90 rtl:rotate-180' />
    </SidebarMenuSubButton>
  ) : (
    <SidebarMenuButton tooltip={item.title}>
      {item.icon && <item.icon />}
      <span>{item.title}</span>
      {item.badge && <NavBadge>{item.badge}</NavBadge>}
      <ChevronRight className='ms-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90 rtl:rotate-180' />
    </SidebarMenuButton>
  )

  const content = (
    <SidebarMenuSub>
      {item.items.map((subItem, index) =>
        subItem.items ? (
          <SidebarCollapsibleNode
            key={`${childKeyPrefix}-collapsible-${index}-${subItem.title}`}
            item={subItem}
            href={href}
            level={level + 1}
          />
        ) : (
          <SidebarMenuSubItem key={`${childKeyPrefix}-link-${index}-${subItem.title}`}>
            <SidebarMenuSubButton asChild isActive={checkIsActive(href, subItem)}>
              <Link to={subItem.url} onClick={() => setOpenMobile(false)}>
                {subItem.icon && <subItem.icon />}
                <span>{subItem.title}</span>
                {subItem.badge && <NavBadge>{subItem.badge}</NavBadge>}
              </Link>
            </SidebarMenuSubButton>
          </SidebarMenuSubItem>
        )
      )}
    </SidebarMenuSub>
  )

  if (level > 0) {
    return (
      <Collapsible
        asChild
        defaultOpen={checkIsActive(href, item, true)}
        className='group/collapsible'
      >
        <SidebarMenuSubItem>
          <CollapsibleTrigger asChild>{trigger}</CollapsibleTrigger>
          <CollapsibleContent className='CollapsibleContent'>{content}</CollapsibleContent>
        </SidebarMenuSubItem>
      </Collapsible>
    )
  }

  return (
    <Collapsible
      asChild
      defaultOpen={checkIsActive(href, item, true)}
      className='group/collapsible'
    >
      <SidebarMenuItem>
        <CollapsibleTrigger asChild>
          {trigger}
        </CollapsibleTrigger>
        <CollapsibleContent className='CollapsibleContent'>{content}</CollapsibleContent>
      </SidebarMenuItem>
    </Collapsible>
  )
}

function SidebarMenuCollapsedDropdown({
  item,
  href,
}: {
  item: NavCollapsible
  href: string
}) {
  const { setOpenMobile } = useSidebar()

  const renderDropdownItems = (nodes: NavItem[], level = 0): ReactNode[] => {
    const rows: ReactNode[] = []

    nodes.forEach((node, index) => {
      const key = `${node.title}-${node.url ?? 'group'}-${level}-${index}`
      const indentStyle = level > 0 ? { paddingLeft: `${Math.min(level * 12, 36)}px` } : undefined

      if (node.items) {
        rows.push(
          <DropdownMenuItem key={`${key}-group`} disabled style={indentStyle}>
            {node.icon && <node.icon />}
            <span className='max-w-52 text-wrap'>{node.title}</span>
          </DropdownMenuItem>
        )
        rows.push(...renderDropdownItems(node.items, level + 1))
        return
      }

      rows.push(
        <DropdownMenuItem key={key} asChild>
            <Link
              to={node.url}
              onClick={() => setOpenMobile(false)}
              className={checkIsActive(href, node) ? 'bg-secondary' : ''}
              style={indentStyle}
            >
            {node.icon && <node.icon />}
            <span className='max-w-52 text-wrap'>{node.title}</span>
            {node.badge && <span className='ms-auto text-xs'>{node.badge}</span>}
          </Link>
        </DropdownMenuItem>
      )
    })

    return rows
  }

  return (
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <SidebarMenuButton
            tooltip={item.title}
            isActive={checkIsActive(href, item)}
          >
            {item.icon && <item.icon />}
            <span>{item.title}</span>
            {item.badge && <NavBadge>{item.badge}</NavBadge>}
            <ChevronRight className='ms-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90' />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent side='right' align='start' sideOffset={4}>
          <DropdownMenuLabel>
            {item.title} {item.badge ? `(${item.badge})` : ''}
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          {renderDropdownItems(item.items)}
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  )
}

function checkIsActive(href: string, item: NavItem, mainNav = false) {
  const hasActiveChild = (nodes?: NavItem[]): boolean => {
    if (!nodes?.length) return false
    return nodes.some((node) => {
      if (node.url) {
        return href === node.url || href.split('?')[0] === node.url
      }
      return hasActiveChild(node.items)
    })
  }

  return (
    href === item.url || // /endpint?search=param
    href.split('?')[0] === item.url || // endpoint
    hasActiveChild(item.items) || // nested nav active
    (mainNav &&
      href.split('/')[1] !== '' &&
      href.split('/')[1] === item?.url?.split('/')[1])
  )
}
