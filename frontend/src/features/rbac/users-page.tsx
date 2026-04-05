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
import { Pencil, Trash2, X } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DictDisplay } from '@/components/dict-display'
import { DictSelect } from '@/components/dict-select'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
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
import { userApi } from '@/api/user'
import type { User, UserQueryRole } from '@/api/user/types'
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
import { useDictOptions } from '@/hooks/use-dict-options'
import { PermissionGate } from './permission-gate'

const emptyForm = {
  username: '',
  nickname: '',
  password: '',
  status: 'ACTIVE' as 'ACTIVE' | 'DISABLED',
  roleIds: [] as number[],
}

type UserTableRow = User & {
  roleCodes: string[]
}

export function UsersPage() {
  const { auth } = useAuthStore()
  const [users, setUsers] = useState<User[]>([])
  const [roles, setRoles] = useState<UserQueryRole[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<User | null>(null)
  const [form, setForm] = useState(emptyForm)

  const [rowSelection, setRowSelection] = useState<RowSelectionState>({})
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [sorting, setSorting] = useState<SortingState>([])
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])
  const [pagination, setPagination] = useState({ pageIndex: 0, pageSize: 10 })

  const canView = auth.permissions.includes('system:user:page')
  const canEditUser = auth.permissions.includes('system:user:edit')
  const canDeleteUser = auth.permissions.includes('system:user:delete')
  const { data: userStatusOptions = [] } = useDictOptions('user_status', canView)

  async function loadData() {
    setLoading(true)
    setError('')

    try {
      const [userRes, roleRes] = await Promise.all([
        userApi.list(),
        userApi.roleOptions(),
      ])
      setUsers(userRes)
      setRoles(roleRes)
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

  const tableData = useMemo<UserTableRow[]>(
    () => users.map((u) => ({ ...u, roleCodes: u.roles.map((r) => r.code) })),
    [users]
  )

  function openCreate() {
    setEditing(null)
    setForm(emptyForm)
    setOpen(true)
  }

  function openEdit(user: User) {
    setEditing(user)
    setForm({
      username: user.username,
      nickname: user.nickname,
      password: '',
      status: user.status,
      roleIds: user.roles.map((r) => r.id),
    })
    setOpen(true)
  }

  async function submitForm() {
    const payload = {
      username: form.username,
      nickname: form.nickname,
      status: form.status,
      roleIds: form.roleIds,
      ...(editing ? {} : { password: form.password }),
      ...(editing && form.password ? { password: form.password } : {}),
    }

    if (editing) {
      await userApi.update(editing.id, payload)
    } else {
      await userApi.create({
        username: payload.username,
        nickname: payload.nickname,
        password: payload.password || '',
        status: payload.status || 'ACTIVE',
        roleIds: payload.roleIds || [],
      })
    }

    setOpen(false)
    setEditing(null)
    setForm(emptyForm)
    await loadData()
  }

  async function deleteUser(id: number) {
    if (!window.confirm('确认删除该用户吗？')) return
    await userApi.remove(id)
    await loadData()
  }

  const columns = useMemo<ColumnDef<UserTableRow>[]>(
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
        accessorKey: 'username',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='账号' />
        ),
      },
      {
        accessorKey: 'nickname',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='昵称' />
        ),
      },
      {
        accessorKey: 'status',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='状态' />
        ),
        cell: ({ row }) =>
          (
            <DictDisplay
              dictCode='user_status'
              value={row.original.status}
              mode='badge'
            />
          ),
      },
      {
        id: 'roles',
        accessorFn: (row) => row.roleCodes,
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='角色' />
        ),
        cell: ({ row }) => row.original.roles.map((r) => r.name).join(' / ') || '-',
        filterFn: (row, _id, value: string[]) => {
          if (!value || value.length === 0) return true
          return value.some((v) => row.original.roleCodes.includes(v))
        },
      },
      {
        accessorKey: 'createdAt',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title='创建时间' />
        ),
        cell: ({ row }) => new Date(row.original.createdAt).toLocaleString(),
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
              <DropdownMenuContent align='end' className='w-[160px]'>
                <DropdownMenuItem
                  disabled={!canEditUser}
                  onClick={() => openEdit(row.original)}
                >
                  编辑
                  <DropdownMenuShortcut>
                    <Pencil size={16} />
                  </DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant='destructive'
                  disabled={!canDeleteUser}
                  onClick={() => void deleteUser(row.original.id)}
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
    [canDeleteUser, canEditUser]
  )

  const table = useReactTable({
    data: tableData,
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

  const selectedUsers = table.getFilteredSelectedRowModel().rows
  const selectedUserCount = selectedUsers.length

  async function bulkDeleteUsers() {
    if (selectedUserCount === 0) return
    if (!window.confirm(`确认批量删除 ${selectedUserCount} 个用户吗？`)) return

    const ids = selectedUsers.map((row) => row.original.id)
    await Promise.allSettled(ids.map((id) => userApi.remove(id)))
    table.resetRowSelection()
    await loadData()
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有用户管理页面权限。
          </div>
        ) : (
          <div className='space-y-4'>
            <div className='flex items-center justify-between'>
              <div>
                <h2 className='text-2xl font-bold tracking-tight'>用户管理</h2>
                <p className='text-muted-foreground'>
                  管理后台用户、角色和状态。
                </p>
              </div>
              <PermissionGate permission='system:user:add'>
                <Button onClick={openCreate}>新增用户</Button>
              </PermissionGate>
            </div>

            <DataTableToolbar
              table={table}
              searchPlaceholder='搜索账号...'
              searchKey='username'
              filters={[
                {
                  columnId: 'status',
                  title: '状态',
                  options: userStatusOptions.map((item) => ({
                    label: item.label,
                    value: item.value,
                  })),
                },
                {
                  columnId: 'roles',
                  title: '角色',
                  options: roles.map((r) => ({ label: r.name, value: r.code })),
                },
              ]}
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

            {selectedUserCount > 0 && (
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
                    已选择 <Badge className='mx-1'>{selectedUserCount}</Badge> 项
                  </div>
                  <PermissionGate permission='system:user:delete'>
                    <Button
                      variant='destructive'
                      size='sm'
                      onClick={() => void bulkDeleteUsers()}
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
              <DialogTitle>{editing ? '编辑用户' : '新增用户'}</DialogTitle>
            </DialogHeader>

            <div className='grid grid-cols-1 gap-3 md:grid-cols-2'>
              <div className='space-y-1'>
                <label className='text-sm'>账号</label>
                <Input
                  value={form.username}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, username: e.target.value }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>昵称</label>
                <Input
                  value={form.nickname}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, nickname: e.target.value }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>密码{editing ? '（留空不改）' : ''}</label>
                <Input
                  type='password'
                  value={form.password}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, password: e.target.value }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>状态</label>
                <DictSelect
                  dictCode='user_status'
                  value={form.status}
                  onValueChange={(value) =>
                    setForm((f) => ({
                      ...f,
                      status: value as 'ACTIVE' | 'DISABLED',
                    }))
                  }
                />
              </div>
            </div>

            <div className='space-y-2'>
              <label className='text-sm'>角色</label>
              <div className='grid max-h-40 grid-cols-2 gap-2 overflow-auto rounded-md border p-3'>
                {roles.map((r) => {
                  const checked = form.roleIds.includes(r.id)
                  return (
                    <label key={r.id} className='flex items-center gap-2 text-sm'>
                      <input
                        type='checkbox'
                        checked={checked}
                        onChange={(e) => {
                          const next = e.target.checked
                            ? [...form.roleIds, r.id]
                            : form.roleIds.filter((id) => id !== r.id)
                          setForm((f) => ({ ...f, roleIds: next }))
                        }}
                      />
                      {r.name}
                    </label>
                  )
                })}
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
