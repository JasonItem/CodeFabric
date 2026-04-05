import { useEffect, useMemo, useState } from 'react'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import {
  type ColumnDef,
  type ColumnFiltersState,
  type RowSelectionState,
  type SortingState,
  type VisibilityState,
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table'
import { ChevronDown, ChevronRight, Pencil, ShieldCheck, Trash2, X } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DataTableColumnHeader,
  DataTablePagination,
  DataTableToolbar,
} from '@/components/data-table'
import { menuApi } from '@/api/menu'
import type { MenuRow } from '@/api/menu/types'
import { roleApi } from '@/api/role'
import type { RoleRow } from '@/api/role/types'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useAuthStore } from '@/stores/auth-store'
import { PermissionGate } from './permission-gate'

const emptyForm = {
  name: '',
  code: '',
  description: '',
}

type MenuTreeNode = MenuRow & { children: MenuTreeNode[] }

export function RolesPage() {
  const { auth } = useAuthStore()
  const [roles, setRoles] = useState<RoleRow[]>([])
  const [menus, setMenus] = useState<MenuRow[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<RoleRow | null>(null)
  const [form, setForm] = useState(emptyForm)

  const [grantOpen, setGrantOpen] = useState(false)
  const [grantRole, setGrantRole] = useState<RoleRow | null>(null)
  const [checkedIds, setCheckedIds] = useState<number[]>([])
  const [grantExpandedIds, setGrantExpandedIds] = useState<Set<number>>(new Set())

  const [rowSelection, setRowSelection] = useState<RowSelectionState>({})
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [pagination, setPagination] = useState({ pageIndex: 0, pageSize: 10 })

  const canView = auth.permissions.includes('system:role:page')
  const canEditRole = auth.permissions.includes('system:role:edit')
  const canAssignRole = auth.permissions.includes('system:role:assign')
  const canDeleteRole = auth.permissions.includes('system:role:delete')

  async function loadData() {
    setLoading(true)
    setError('')

    try {
      const [roleRes, menuRes] = await Promise.all([
        roleApi.list(),
        menuApi.list(),
      ])
      setRoles(roleRes)
      setMenus(menuRes)
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

  function openCreate() {
    setEditing(null)
    setForm(emptyForm)
    setOpen(true)
  }

  function openEdit(role: RoleRow) {
    setEditing(role)
    setForm({
      name: role.name,
      code: role.code,
      description: role.description || '',
    })
    setOpen(true)
  }

  async function submitRole() {
    if (editing) {
      await roleApi.update(editing.id, form)
    } else {
      await roleApi.create(form)
    }

    setOpen(false)
    setEditing(null)
    setForm(emptyForm)
    await loadData()
  }

  async function deleteRole(id: number) {
    if (!window.confirm('确认删除该角色吗？')) return
    await roleApi.remove(id)
    await loadData()
  }

  function openGrant(role: RoleRow) {
    setGrantRole(role)
    setCheckedIds(role.menuIds)
    setGrantExpandedIds(new Set(menus.map((m) => m.id)))
    setGrantOpen(true)
  }

  async function submitGrant() {
    if (!grantRole) return
    await roleApi.assignPermissions(grantRole.id, {
      menuIds: checkedIds,
    })
    setGrantOpen(false)
    setGrantRole(null)
    await loadData()
  }

  const menuTreeRoots = useMemo(() => {
    const map = new Map<number, MenuTreeNode>()
    const roots: MenuTreeNode[] = []

    menus
      .slice()
      .sort((a, b) => a.sort - b.sort || a.id - b.id)
      .forEach((m) => map.set(m.id, { ...m, children: [] }))

    for (const node of map.values()) {
      if (node.parentId && map.has(node.parentId)) {
        map.get(node.parentId)!.children.push(node)
      } else {
        roots.push(node)
      }
    }
    return roots
  }, [menus])

  const childrenMap = useMemo(() => {
    const map = new Map<number, number[]>()
    menus.forEach((m) => {
      if (!m.parentId) return
      if (!map.has(m.parentId)) map.set(m.parentId, [])
      map.get(m.parentId)!.push(m.id)
    })
    return map
  }, [menus])

  const parentMap = useMemo(() => {
    const map = new Map<number, number | null>()
    menus.forEach((m) => map.set(m.id, m.parentId))
    return map
  }, [menus])

  function getDescendants(id: number) {
    const result: number[] = []
    const stack = [...(childrenMap.get(id) || [])]
    while (stack.length > 0) {
      const current = stack.pop()!
      result.push(current)
      const children = childrenMap.get(current) || []
      stack.push(...children)
    }
    return result
  }

  function getNodeState(id: number, checkedSet: Set<number>) {
    if (checkedSet.has(id)) return 'checked' as const
    const descendants = getDescendants(id)
    if (descendants.some((descId) => checkedSet.has(descId))) {
      return 'indeterminate' as const
    }
    return 'unchecked' as const
  }

  function togglePermission(id: number, checked: boolean) {
    setCheckedIds((prev) => {
      const next = new Set(prev)
      const descendants = getDescendants(id)

      if (checked) {
        next.add(id)
        descendants.forEach((d) => next.add(d))
      } else {
        next.delete(id)
        descendants.forEach((d) => next.delete(d))
      }

      let parentId = parentMap.get(id) ?? null
      while (parentId) {
        const childIds = childrenMap.get(parentId) || []
        const allChecked = childIds.every((childId) => next.has(childId))
        if (allChecked) next.add(parentId)
        else next.delete(parentId)
        parentId = parentMap.get(parentId) ?? null
      }

      return Array.from(next)
    })
  }

  function toggleGrantExpand(id: number) {
    setGrantExpandedIds((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  const columns = useMemo<ColumnDef<RoleRow>[]>(
    () => [
      {
        id: 'select',
        header: ({ table }) => (
          <Checkbox
            checked={
              table.getIsAllPageRowsSelected() ||
              (table.getIsSomePageRowsSelected() && 'indeterminate')
            }
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label='全选当前页'
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label='选择当前行'
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorKey: 'id',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='ID' />
        ),
      },
      {
        accessorKey: 'name',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='角色名称' />
        ),
      },
      {
        accessorKey: 'code',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='角色编码' />
        ),
        cell: ({ row }) => <Badge variant='outline'>{row.original.code}</Badge>,
      },
      {
        accessorKey: 'description',
        header: '描述',
        cell: ({ row }) => row.original.description || '-',
      },
      {
        accessorKey: 'userCount',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='用户数' />
        ),
      },
      {
        accessorKey: 'permissionCount',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='权限数' />
        ),
      },
      {
        id: 'actions',
        header: () => <div className='text-right'>操作</div>,
        enableSorting: false,
        cell: ({ row }) => (
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
              <DropdownMenuContent align='end' className='w-[180px]'>
                <DropdownMenuItem
                  disabled={!canEditRole}
                  onClick={() => openEdit(row.original)}
                >
                  编辑
                  <DropdownMenuShortcut>
                    <Pencil size={16} />
                  </DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuItem
                  disabled={!canAssignRole}
                  onClick={() => openGrant(row.original)}
                >
                  分配权限
                  <DropdownMenuShortcut>
                    <ShieldCheck size={16} />
                  </DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant='destructive'
                  disabled={!canDeleteRole}
                  onClick={() => void deleteRole(row.original.id)}
                >
                  删除
                  <DropdownMenuShortcut>
                    <Trash2 size={16} />
                  </DropdownMenuShortcut>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        ),
      },
    ],
    [canAssignRole, canDeleteRole, canEditRole]
  )

  const table = useReactTable({
    data: roles,
    columns,
    enableRowSelection: true,
    state: {
      sorting,
      pagination,
      rowSelection,
      columnFilters,
      columnVisibility,
    },
    onPaginationChange: setPagination,
    onColumnFiltersChange: setColumnFilters,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
    getRowId: (row) => String(row.id),
  })

  const selectedRoles = table.getFilteredSelectedRowModel().rows
  const selectedRoleCount = selectedRoles.length

  async function bulkDeleteRoles() {
    if (selectedRoleCount === 0) return
    if (!window.confirm(`确认批量删除 ${selectedRoleCount} 个角色吗？`)) return

    const ids = selectedRoles.map((row) => row.original.id)
    await Promise.allSettled(ids.map((id) => roleApi.remove(id)))
    table.resetRowSelection()
    await loadData()
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有角色管理页面权限。
          </div>
        ) : (
          <div className='space-y-4'>
            <div className='flex items-center justify-between'>
              <div>
                <h2 className='text-2xl font-bold tracking-tight'>角色管理</h2>
                <p className='text-muted-foreground'>
                  管理角色、权限分配和成员范围。
                </p>
              </div>
              <PermissionGate permission='system:role:add'>
                <Button onClick={openCreate}>新增角色</Button>
              </PermissionGate>
            </div>

            <DataTableToolbar
              table={table}
              searchPlaceholder='搜索角色名称...'
              searchKey='name'
            />

            {error && <p className='text-sm text-red-600'>{error}</p>}

            <div className='overflow-hidden rounded-md border'>
              <Table>
                <TableHeader>
                  {table.getHeaderGroups().map((headerGroup) => (
                    <TableRow key={headerGroup.id}>
                      {headerGroup.headers.map((header) => (
                        <TableHead
                          key={header.id}
                          className={
                            header.column.id === 'actions'
                              ? 'sticky right-0 z-20 w-[72px] bg-background'
                              : undefined
                          }
                        >
                          {header.isPlaceholder
                            ? null
                            : flexRender(
                                header.column.columnDef.header,
                                header.getContext()
                              )}
                        </TableHead>
                      ))}
                    </TableRow>
                  ))}
                </TableHeader>
                <TableBody>
                  {loading ? (
                    <TableRow>
                      <TableCell colSpan={columns.length} className='h-24 text-center'>
                        加载中...
                      </TableCell>
                    </TableRow>
                  ) : table.getRowModel().rows.length > 0 ? (
                    table.getRowModel().rows.map((row) => (
                      <TableRow key={row.id}>
                        {row.getVisibleCells().map((cell) => (
                          <TableCell
                            key={cell.id}
                            className={
                              cell.column.id === 'actions'
                                ? 'sticky right-0 z-10 bg-background'
                                : undefined
                            }
                          >
                            {flexRender(
                              cell.column.columnDef.cell,
                              cell.getContext()
                            )}
                          </TableCell>
                        ))}
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={columns.length} className='h-24 text-center'>
                        暂无数据
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            <DataTablePagination table={table} />

            {selectedRoleCount > 0 && (
              <div className='fixed bottom-6 left-1/2 z-50 -translate-x-1/2'>
                <div className='flex items-center gap-2 rounded-xl border bg-background/95 p-2 shadow-xl backdrop-blur supports-backdrop-filter:bg-background/60'>
                  <Button
                    variant='outline'
                    size='icon'
                    className='size-8 rounded-full'
                    onClick={() => table.resetRowSelection()}
                  >
                    <X className='h-4 w-4' />
                  </Button>
                  <div className='text-sm'>
                    已选择 <Badge className='mx-1'>{selectedRoleCount}</Badge> 项
                  </div>
                  <PermissionGate permission='system:role:delete'>
                    <Button
                      variant='destructive'
                      size='sm'
                      onClick={() => void bulkDeleteRoles()}
                    >
                      <Trash2 className='mr-1 h-4 w-4' />
                      批量删除
                    </Button>
                  </PermissionGate>
                </div>
              </div>
            )}
          </div>
        )}

        <Dialog open={open} onOpenChange={setOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editing ? '编辑角色' : '新增角色'}</DialogTitle>
            </DialogHeader>
            <div className='space-y-3'>
              <div className='space-y-1'>
                <label className='text-sm'>角色名称</label>
                <Input
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>角色编码</label>
                <Input
                  value={form.code}
                  onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>描述</label>
                <Textarea
                  rows={3}
                  value={form.description}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, description: e.target.value }))
                  }
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant='outline' onClick={() => setOpen(false)}>
                取消
              </Button>
              <Button onClick={() => void submitRole()}>保存</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <Dialog open={grantOpen} onOpenChange={setGrantOpen}>
          <DialogContent className='sm:max-w-2xl'>
            <DialogHeader>
              <DialogTitle>分配权限 - {grantRole?.name}</DialogTitle>
            </DialogHeader>
            <div className='max-h-[520px] space-y-1 overflow-auto rounded-md border p-3'>
              {menuTreeRoots.map((root) => (
                <GrantTreeNode
                  key={root.id}
                  node={root}
                  depth={0}
                  checkedIds={checkedIds}
                  expandedIds={grantExpandedIds}
                  onToggleExpand={toggleGrantExpand}
                  onToggleCheck={togglePermission}
                  getNodeState={getNodeState}
                />
              ))}
            </div>
            <DialogFooter>
              <Button variant='outline' onClick={() => setGrantOpen(false)}>
                取消
              </Button>
              <Button onClick={() => void submitGrant()}>保存</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </Main>
    </>
  )
}

function GrantTreeNode({
  node,
  depth,
  checkedIds,
  expandedIds,
  onToggleExpand,
  onToggleCheck,
  getNodeState,
}: {
  node: MenuTreeNode
  depth: number
  checkedIds: number[]
  expandedIds: Set<number>
  onToggleExpand: (id: number) => void
  onToggleCheck: (id: number, checked: boolean) => void
  getNodeState: (
    id: number,
    checkedSet: Set<number>
  ) => 'checked' | 'indeterminate' | 'unchecked'
}) {
  const hasChildren = node.children.length > 0
  const expanded = expandedIds.has(node.id)
  const state = getNodeState(node.id, new Set(checkedIds))

  return (
    <>
      <div
        className='flex items-center gap-2 rounded px-2 py-1.5 hover:bg-muted/60'
        style={{ paddingLeft: `${8 + depth * 18}px` }}
      >
        {hasChildren ? (
          <button
            type='button'
            className='inline-flex h-4 w-4 items-center justify-center'
            onClick={() => onToggleExpand(node.id)}
          >
            {expanded ? (
              <ChevronDown className='h-4 w-4 text-muted-foreground' />
            ) : (
              <ChevronRight className='h-4 w-4 text-muted-foreground' />
            )}
          </button>
        ) : (
          <span className='inline-block h-4 w-4' />
        )}

        <Checkbox
          checked={
            state === 'checked'
              ? true
              : state === 'indeterminate'
                ? 'indeterminate'
                : false
          }
          onCheckedChange={(value) => onToggleCheck(node.id, !!value)}
        />
        <span className='text-sm'>
          {node.name}
          {node.permissionKey ? (
            <span className='text-muted-foreground'> ({node.permissionKey})</span>
          ) : null}
        </span>
      </div>

      {hasChildren &&
        expanded &&
        node.children
          .sort((a, b) => a.sort - b.sort || a.id - b.id)
          .map((child) => (
            <GrantTreeNode
              key={child.id}
              node={child}
              depth={depth + 1}
              checkedIds={checkedIds}
              expandedIds={expandedIds}
              onToggleExpand={onToggleExpand}
              onToggleCheck={onToggleCheck}
              getNodeState={getNodeState}
            />
          ))}
    </>
  )
}
