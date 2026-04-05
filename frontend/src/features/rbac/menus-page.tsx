import { useEffect, useMemo, useRef, useState } from 'react'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { ChevronDown, ChevronRight, Pencil, Plus, Trash2 } from 'lucide-react'
import { menuApi } from '@/api/menu'
import type { MenuRow } from '@/api/menu/types'
import { authApi } from '@/api/auth'
import { Main } from '@/components/layout/main'
import { DictDisplay } from '@/components/dict-display'
import { DictSelect } from '@/components/dict-select'
import { IconPicker, LucideIconByName } from '@/components/icon-picker'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAuthStore } from '@/stores/auth-store'
import { PermissionGate } from './permission-gate'

const emptyForm = {
  parentId: '',
  name: '',
  path: '',
  component: '',
  icon: '',
  type: 'MENU' as 'DIRECTORY' | 'MENU' | 'BUTTON',
  permissionKey: '',
  sort: 0,
  visible: true,
}

type MenuNode = MenuRow & {
  children: MenuNode[]
}

type FlatTreeRow = {
  node: MenuNode
  depth: number
  hasChildren: boolean
}

export function MenusPage() {
  const { auth } = useAuthStore()
  const [menus, setMenus] = useState<MenuRow[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [open, setOpen] = useState(false)
  const dialogContentRef = useRef<HTMLDivElement | null>(null)
  const [editing, setEditing] = useState<MenuRow | null>(null)
  const [form, setForm] = useState(emptyForm)
  const [parentPickerOpen, setParentPickerOpen] = useState(false)
  const [parentExpandedIds, setParentExpandedIds] = useState<Set<number>>(new Set())

  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set())
  const [nameQuery, setNameQuery] = useState('')
  const [pathQuery, setPathQuery] = useState('')
  const [componentQuery, setComponentQuery] = useState('')
  const [permQuery, setPermQuery] = useState('')
  const [searchApplied, setSearchApplied] = useState({
    name: '',
    path: '',
    component: '',
    perm: '',
  })

  const canView = auth.permissions.includes('system:menu:page')
  const canAddMenu = auth.permissions.includes('system:menu:add')
  const canEditMenu = auth.permissions.includes('system:menu:edit')
  const canDeleteMenu = auth.permissions.includes('system:menu:delete')
  const isButtonType = form.type === 'BUTTON'

  async function refreshSessionMenus() {
    try {
      const session = await authApi.me()
      auth.setSession(session)
    } catch {
      // ignore: menu page can continue to work with current local state
    }
  }

  async function loadData() {
    setLoading(true)
    setError('')

    try {
      const list = await menuApi.list()
      setMenus(list)
      setExpandedIds(new Set(list.map((m) => m.id)))
    } catch (e) {
      setError(e instanceof Error ? e.message : '加载失败')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    if (!canView) return
    void loadData()
  }, [canView])

  const filteredMenus = useMemo(() => {
    const name = searchApplied.name.trim().toLowerCase()
    const path = searchApplied.path.trim().toLowerCase()
    const component = searchApplied.component.trim().toLowerCase()
    const perm = searchApplied.perm.trim().toLowerCase()

    if (!name && !path && !component && !perm) return menus

    const map = new Map(menus.map((m) => [m.id, m]))
    const matched = menus.filter((m) => {
      const n = m.name.toLowerCase().includes(name)
      const p = (m.path || '').toLowerCase().includes(path)
      const c = (m.component || '').toLowerCase().includes(component)
      const k = (m.permissionKey || '').toLowerCase().includes(perm)
      return n && p && c && k
    })

    const includeIds = new Set<number>()
    matched.forEach((m) => {
      includeIds.add(m.id)
      let cursor = m.parentId
      while (cursor) {
        includeIds.add(cursor)
        cursor = map.get(cursor)?.parentId ?? null
      }
    })

    return menus.filter((m) => includeIds.has(m.id))
  }, [menus, searchApplied])

  const treeRows = useMemo<FlatTreeRow[]>(() => {
    const nodeMap = new Map<number, MenuNode>()
    const roots: MenuNode[] = []

    filteredMenus
      .slice()
      .sort((a, b) => a.sort - b.sort || a.id - b.id)
      .forEach((m) => nodeMap.set(m.id, { ...m, children: [] }))

    for (const node of nodeMap.values()) {
      if (node.parentId && nodeMap.has(node.parentId)) {
        nodeMap.get(node.parentId)!.children.push(node)
      } else {
        roots.push(node)
      }
    }

    const flat: FlatTreeRow[] = []
    function travel(nodes: MenuNode[], depth: number) {
      nodes.forEach((node) => {
        const hasChildren = node.children.length > 0
        flat.push({ node, depth, hasChildren })
        if (hasChildren && expandedIds.has(node.id)) {
          travel(node.children, depth + 1)
        }
      })
    }

    travel(roots.sort((a, b) => a.sort - b.sort || a.id - b.id), 0)
    return flat
  }, [filteredMenus, expandedIds])

  function openCreate(parentId?: number | null) {
    setEditing(null)
    setForm({ ...emptyForm, parentId: parentId ? String(parentId) : '' })
    setParentPickerOpen(false)
    setOpen(true)
  }

  function openEdit(menu: MenuRow) {
    setEditing(menu)
    setForm({
      parentId: menu.parentId ? String(menu.parentId) : '',
      name: menu.name,
      path: menu.path || '',
      component: menu.component || '',
      icon: menu.icon || '',
      type: menu.type,
      permissionKey: menu.permissionKey || '',
      sort: menu.sort,
      visible: menu.visible,
    })
    setParentPickerOpen(false)
    setOpen(true)
  }

  function toggleExpand(id: number) {
    setExpandedIds((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  function expandAll() {
    setExpandedIds(new Set(filteredMenus.map((m) => m.id)))
  }

  function collapseAll() {
    setExpandedIds(new Set())
  }

  function applySearch() {
    setSearchApplied({
      name: nameQuery,
      path: pathQuery,
      component: componentQuery,
      perm: permQuery,
    })
  }

  function resetSearch() {
    setNameQuery('')
    setPathQuery('')
    setComponentQuery('')
    setPermQuery('')
    setSearchApplied({ name: '', path: '', component: '', perm: '' })
  }

  async function submitForm() {
    if (!form.name.trim()) {
      window.alert('请输入菜单名称')
      return
    }

    if (isButtonType && !form.permissionKey.trim()) {
      window.alert('按钮类型必须填写权限标识')
      return
    }

    const pathValue = isButtonType ? null : form.path || null
    const componentValue = isButtonType ? null : form.component || null
    const iconValue = isButtonType ? null : form.icon || null
    const visibleValue = isButtonType ? false : form.visible

    const payload = {
      parentId: form.parentId ? Number(form.parentId) : null,
      name: form.name.trim(),
      path: pathValue,
      component: componentValue,
      icon: iconValue,
      type: form.type,
      permissionKey: form.permissionKey.trim() || null,
      sort: Number(form.sort || 0),
      visible: visibleValue,
    }

    if (editing) {
      await menuApi.update(editing.id, payload)
    } else {
      await menuApi.create(payload)
    }

    setOpen(false)
    setEditing(null)
    setForm(emptyForm)
    await loadData()
    await refreshSessionMenus()
  }

  async function deleteMenu(id: number) {
    if (!window.confirm('确认删除该菜单吗？')) return
    await menuApi.remove(id)
    await loadData()
    await refreshSessionMenus()
  }

  const parentOptions = useMemo(
    () => menus.filter((m) => m.type !== 'BUTTON'),
    [menus]
  )

  const disabledParentIds = useMemo(() => {
    if (!editing) return new Set<number>()

    const childrenMap = new Map<number, number[]>()
    menus.forEach((m) => {
      if (!m.parentId) return
      if (!childrenMap.has(m.parentId)) childrenMap.set(m.parentId, [])
      childrenMap.get(m.parentId)!.push(m.id)
    })

    const blocked = new Set<number>([editing.id])
    const stack = [editing.id]
    while (stack.length > 0) {
      const id = stack.pop()!
      const children = childrenMap.get(id) || []
      for (const child of children) {
        if (blocked.has(child)) continue
        blocked.add(child)
        stack.push(child)
      }
    }

    return blocked
  }, [editing, menus])

  const parentTreeRoots = useMemo(() => {
    const nodeMap = new Map<number, MenuNode>()
    const roots: MenuNode[] = []

    parentOptions
      .filter((m) => !disabledParentIds.has(m.id))
      .slice()
      .sort((a, b) => a.sort - b.sort || a.id - b.id)
      .forEach((m) => nodeMap.set(m.id, { ...m, children: [] }))

    for (const node of nodeMap.values()) {
      if (node.parentId && nodeMap.has(node.parentId)) {
        nodeMap.get(node.parentId)!.children.push(node)
      } else {
        roots.push(node)
      }
    }

    return roots.sort((a, b) => a.sort - b.sort || a.id - b.id)
  }, [parentOptions, disabledParentIds])

  useEffect(() => {
    if (!open) return
    setParentExpandedIds(new Set(parentTreeRoots.map((n) => n.id)))
  }, [open, parentTreeRoots])

  const selectedParentName = useMemo(() => {
    if (!form.parentId) return ''
    const id = Number(form.parentId)
    return menus.find((m) => m.id === id)?.name || ''
  }, [form.parentId, menus])

  function toggleParentExpand(id: number) {
    setParentExpandedIds((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  function ParentTreeNode({
    node,
    depth,
  }: {
    node: MenuNode
    depth: number
  }) {
    const hasChildren = node.children.length > 0
    const expanded = parentExpandedIds.has(node.id)
    return (
      <>
        <button
          type='button'
          className='flex w-full items-center gap-1 rounded px-2 py-1.5 text-left text-sm hover:bg-muted'
          style={{ paddingLeft: `${8 + depth * 18}px` }}
          onClick={() => {
            setForm((f) => ({ ...f, parentId: String(node.id) }))
            setParentPickerOpen(false)
          }}
        >
          {hasChildren ? (
            <span
              className='inline-flex h-4 w-4 items-center justify-center'
              onClick={(e) => {
                e.stopPropagation()
                toggleParentExpand(node.id)
              }}
            >
              {expanded ? (
                <ChevronDown className='h-4 w-4 text-muted-foreground' />
              ) : (
                <ChevronRight className='h-4 w-4 text-muted-foreground' />
              )}
            </span>
          ) : (
            <span className='inline-block h-4 w-4' />
          )}
          <span>{node.name}</span>
        </button>
        {hasChildren &&
          expanded &&
          node.children
            .sort((a, b) => a.sort - b.sort || a.id - b.id)
            .map((child) => (
              <ParentTreeNode key={child.id} node={child} depth={depth + 1} />
            ))}
      </>
    )
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有菜单管理页面权限。
          </div>
        ) : (
          <>
            <div className='flex items-center justify-between'>
              <div>
                <h2 className='text-2xl font-bold tracking-tight'>菜单管理</h2>
                <p className='text-muted-foreground'>
                  菜单、路由与按钮权限统一在此维护。
                </p>
              </div>
              <div className='flex items-center gap-2'>
                <Button variant='outline' size='sm' onClick={expandAll}>
                  展开全部
                </Button>
                <Button variant='outline' size='sm' onClick={collapseAll}>
                  折叠全部
                </Button>
                <PermissionGate permission='system:menu:add'>
                  <Button size='sm' onClick={() => openCreate(null)}>
                    <Plus className='mr-1 h-4 w-4' /> 新增
                  </Button>
                </PermissionGate>
              </div>
            </div>

            <div className='grid grid-cols-1 gap-3 md:grid-cols-6'>
              <Input
                placeholder='菜单名称'
                value={nameQuery}
                onChange={(e) => setNameQuery(e.target.value)}
              />
              <Input
                placeholder='菜单地址'
                value={pathQuery}
                onChange={(e) => setPathQuery(e.target.value)}
              />
              <Input
                placeholder='权限标识'
                value={permQuery}
                onChange={(e) => setPermQuery(e.target.value)}
              />
              <Input
                placeholder='组件地址'
                value={componentQuery}
                onChange={(e) => setComponentQuery(e.target.value)}
              />
              <div className='flex gap-2'>
                <Button className='flex-1' onClick={applySearch}>
                  搜索
                </Button>
                <Button variant='outline' className='flex-1' onClick={resetSearch}>
                  重置
                </Button>
              </div>
            </div>

            {error && <p className='text-sm text-red-600'>{error}</p>}
            <div className='overflow-hidden rounded-md border'>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>菜单名称</TableHead>
                    <TableHead>路由地址</TableHead>
                    <TableHead>组件地址</TableHead>
                    <TableHead>排序</TableHead>
                    <TableHead>可见</TableHead>
                    <TableHead>类型</TableHead>
                    <TableHead>权限标识</TableHead>
                    <TableHead className='sticky right-0 z-20 bg-background text-right'>
                      操作
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loading ? (
                    <TableRow>
                      <TableCell colSpan={8} className='h-24 text-center'>
                        加载中...
                      </TableCell>
                    </TableRow>
                  ) : treeRows.length > 0 ? (
                    treeRows.map(({ node, depth, hasChildren }) => (
                      <TableRow key={node.id}>
                        <TableCell>
                          <div
                            className='flex items-center gap-1'
                            style={{ paddingLeft: `${depth * 20}px` }}
                          >
                            {hasChildren ? (
                              <button
                                className='rounded p-0.5 hover:bg-muted'
                                onClick={() => toggleExpand(node.id)}
                              >
                                {expandedIds.has(node.id) ? (
                                  <ChevronDown className='h-4 w-4' />
                                ) : (
                                  <ChevronRight className='h-4 w-4' />
                                )}
                              </button>
                            ) : (
                              <span className='inline-block h-4 w-4' />
                            )}
                            <LucideIconByName
                              name={node.icon}
                              className='h-4 w-4 text-muted-foreground'
                            />
                            <span>{node.name}</span>
                          </div>
                        </TableCell>
                        <TableCell>{node.path || '-'}</TableCell>
                        <TableCell>{node.component || '-'}</TableCell>
                        <TableCell>{node.sort}</TableCell>
                        <TableCell>
                          <DictDisplay
                            dictCode='menu_visible'
                            value={node.visible ? '1' : '0'}
                            mode='badge'
                          />
                        </TableCell>
                        <TableCell>
                          <DictDisplay dictCode='menu_type' value={node.type} mode='badge' />
                        </TableCell>
                        <TableCell>{node.permissionKey || '-'}</TableCell>
                        <TableCell className='sticky right-0 z-10 bg-background'>
                          <div className='flex justify-end'>
                            <DropdownMenu modal={false}>
                              <DropdownMenuTrigger asChild>
                                <Button
                                  variant='ghost'
                                  className='flex h-8 w-8 p-0 data-[state=open]:bg-muted'
                                >
                                  <DotsHorizontalIcon className='h-4 w-4' />
                                  <span className='sr-only'>打开菜单</span>
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align='end' className='w-[160px]'>
                                <DropdownMenuItem
                                  disabled={!canAddMenu}
                                  onClick={() => openCreate(node.id)}
                                >
                                  添加子菜单
                                  <DropdownMenuShortcut>
                                    <Plus className='h-4 w-4' />
                                  </DropdownMenuShortcut>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                  disabled={!canEditMenu}
                                  onClick={() => openEdit(node)}
                                >
                                  编辑
                                  <DropdownMenuShortcut>
                                    <Pencil className='h-4 w-4' />
                                  </DropdownMenuShortcut>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                  variant='destructive'
                                  disabled={!canDeleteMenu}
                                  onClick={() => void deleteMenu(node.id)}
                                >
                                  删除
                                  <DropdownMenuShortcut>
                                    <Trash2 className='h-4 w-4' />
                                  </DropdownMenuShortcut>
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={8} className='h-24 text-center'>
                        暂无数据
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </>
        )}

        <Dialog open={open} onOpenChange={setOpen}>
          <DialogContent className='sm:max-w-2xl' ref={dialogContentRef}>
            <DialogHeader>
              <DialogTitle>{editing ? '编辑菜单' : '新增菜单'}</DialogTitle>
            </DialogHeader>
            <div className='grid grid-cols-1 gap-3 md:grid-cols-2'>
              <div className='space-y-1'>
                <label className='text-sm'>上级菜单</label>
                <Popover open={parentPickerOpen} onOpenChange={setParentPickerOpen}>
                  <PopoverTrigger asChild>
                    <Button
                      type='button'
                      variant='outline'
                      className='w-full justify-between'
                    >
                      <span className='truncate'>
                        {selectedParentName || '请选择上级菜单'}
                      </span>
                      <ChevronDown className='h-4 w-4 opacity-60' />
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent
                    container={dialogContentRef.current}
                    className='w-[var(--radix-popover-trigger-width)] p-1'
                  >
                    <div className='max-h-64 overflow-y-auto overscroll-contain'>
                      <button
                        type='button'
                        className='flex w-full items-center rounded px-2 py-1.5 text-left text-sm hover:bg-muted'
                        onClick={() => {
                          setForm((f) => ({ ...f, parentId: '' }))
                          setParentPickerOpen(false)
                        }}
                      >
                        无
                      </button>
                      {parentTreeRoots.map((root) => (
                        <ParentTreeNode key={root.id} node={root} depth={0} />
                      ))}
                    </div>
                  </PopoverContent>
                </Popover>
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>类型</label>
                <DictSelect
                  dictCode='menu_type'
                  value={form.type}
                  onValueChange={(value) =>
                    setForm((f) => ({
                      ...f,
                      type: value as 'DIRECTORY' | 'MENU' | 'BUTTON',
                      path:
                        value === 'BUTTON' ? '' : f.path,
                      component:
                        value === 'BUTTON' ? '' : f.component,
                      icon:
                        value === 'BUTTON' ? '' : f.icon,
                      visible:
                        value === 'BUTTON' ? false : f.visible,
                    }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>名称</label>
                <Input
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>路由地址</label>
                <Input
                  value={form.path}
                  disabled={isButtonType}
                  onChange={(e) => setForm((f) => ({ ...f, path: e.target.value }))}
                  placeholder={isButtonType ? '按钮类型无需填写' : '如 /users'}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>组件地址</label>
                <Input
                  value={form.component}
                  disabled={isButtonType}
                  onChange={(e) => setForm((f) => ({ ...f, component: e.target.value }))}
                  placeholder={isButtonType ? '按钮类型无需填写' : '如 rbac/users'}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>图标</label>
                <IconPicker
                  disabled={isButtonType}
                  value={form.icon}
                  onValueChange={(icon) => setForm((f) => ({ ...f, icon }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>权限标识</label>
                <Input
                  value={form.permissionKey}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, permissionKey: e.target.value }))
                  }
                  placeholder='如 system:user:list'
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>排序</label>
                <Input
                  type='number'
                  value={form.sort}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, sort: Number(e.target.value) || 0 }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>是否可见</label>
                <DictSelect
                  dictCode='menu_visible'
                  value={form.visible ? '1' : '0'}
                  disabled={isButtonType}
                  onValueChange={(value) =>
                    setForm((f) => ({ ...f, visible: value === '1' }))
                  }
                />
              </div>
            </div>

            <DialogFooter>
              <Button variant='outline' onClick={() => setOpen(false)}>
                取消
              </Button>
              <Button onClick={() => void submitForm()}>保存</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </Main>
    </>
  )
}
