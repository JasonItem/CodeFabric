import React from 'react'
import { useNavigate } from '@tanstack/react-router'
import { ArrowRight, ChevronRight, Laptop, Moon, Sun } from 'lucide-react'
import { toAppPath } from '@/lib/admin-menu'
import { useSearch } from '@/context/search-provider'
import { useTheme } from '@/context/theme-provider'
import { useAuthStore } from '@/stores/auth-store'
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from '@/components/ui/command'
import { ScrollArea } from './ui/scroll-area'

export function CommandMenu() {
  const navigate = useNavigate()
  const { setTheme } = useTheme()
  const { open, setOpen } = useSearch()
  const menus = useAuthStore((s) => s.auth.menus)

  const menuGroups = React.useMemo(() => {
    const routeMenus = menus.filter((menu) => menu.type === 'MENU' && menu.visible && menu.path)
    if (routeMenus.length === 0) return []

    const menuMap = new Map(routeMenus.map((menu) => [menu.id, menu]))
    const groups = new Map<string, Array<{ title: string; url: string; parent?: string }>>()

    const resolveGroupTitle = (menuId: number | null) => {
      let currentId = menuId
      let lastTitle = '页面'
      while (currentId) {
        const current = menuMap.get(currentId)
        if (!current) break
        lastTitle = current.name
        if (!current.parentId) break
        currentId = current.parentId
      }
      return lastTitle
    }

    routeMenus
      .slice()
      .sort((a, b) => a.sort - b.sort || a.id - b.id)
      .forEach((menu) => {
        const groupTitle = resolveGroupTitle(menu.parentId)
        if (!groups.has(groupTitle)) groups.set(groupTitle, [])
        const parent = menu.parentId ? menuMap.get(menu.parentId)?.name : undefined
        groups.get(groupTitle)!.push({
          title: menu.name,
          url: toAppPath(menu.path),
          parent,
        })
      })

    return Array.from(groups.entries()).map(([title, items]) => ({
      title,
      items,
    }))
  }, [menus])

  const runCommand = React.useCallback(
    (command: () => unknown) => {
      setOpen(false)
      command()
    },
    [setOpen]
  )

  return (
    <CommandDialog modal open={open} onOpenChange={setOpen}>
      <CommandInput placeholder='输入命令或搜索...' />
      <CommandList>
        <ScrollArea type='hover' className='h-72 pe-1'>
          <CommandEmpty>未找到结果。</CommandEmpty>
          {menuGroups.map((group) => (
            <CommandGroup key={group.title} heading={group.title}>
              {group.items.map((item, i) => (
                <CommandItem
                  key={`${item.url}-${i}`}
                  value={`${group.title}-${item.parent || ''}-${item.title}`}
                  onSelect={() => {
                    runCommand(() => navigate({ to: item.url }))
                  }}
                >
                  <div className='flex size-4 items-center justify-center'>
                    <ArrowRight className='size-2 text-muted-foreground/80' />
                  </div>
                  {item.parent ? (
                    <>
                      {item.parent} <ChevronRight /> {item.title}
                    </>
                  ) : (
                    item.title
                  )}
                </CommandItem>
              ))}
            </CommandGroup>
          ))}
          <CommandSeparator />
          <CommandGroup heading='主题'>
            <CommandItem onSelect={() => runCommand(() => setTheme('light'))}>
              <Sun /> <span>浅色</span>
            </CommandItem>
            <CommandItem onSelect={() => runCommand(() => setTheme('dark'))}>
              <Moon className='scale-90' />
              <span>深色</span>
            </CommandItem>
            <CommandItem onSelect={() => runCommand(() => setTheme('system'))}>
              <Laptop />
              <span>跟随系统</span>
            </CommandItem>
          </CommandGroup>
        </ScrollArea>
      </CommandList>
    </CommandDialog>
  )
}
