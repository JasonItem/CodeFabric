import { useEffect, useMemo, useState } from 'react'
import { ChevronLeft, ChevronRight, Trash2, XCircle } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { DictDisplay } from '@/components/dict-display'
import { DictSelect } from '@/components/dict-select'
import { DateTimeRangePicker } from '@/components/date-time-range-picker'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { getPageNumbers } from '@/lib/utils'
import { useAuthStore } from '@/stores/auth-store'
import { loginLogApi } from '@/api/login-log'
import type { LoginLogRow } from '@/api/login-log/types'

const defaultFilters = {
  ip: '',
  username: '',
  success: 'all',
  startTime: '',
  endTime: '',
}

export function LoginLogsPage() {
  const { auth } = useAuthStore()
  const canView = auth.permissions.includes('system:login-log:page')
  const canList = auth.permissions.includes('system:login-log:list')
  const canDelete = auth.permissions.includes('system:login-log:delete')

  const [filters, setFilters] = useState(defaultFilters)
  const [appliedFilters, setAppliedFilters] = useState(defaultFilters)

  const [list, setList] = useState<LoginLogRow[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [total, setTotal] = useState(0)

  const [selectedIds, setSelectedIds] = useState<number[]>([])

  async function loadData() {
    if (!canView || !canList) return
    setLoading(true)
    setError('')

    try {
      const data = await loginLogApi.list({
        page,
        pageSize,
        ip: appliedFilters.ip || undefined,
        username: appliedFilters.username || undefined,
        success: appliedFilters.success === 'all' ? undefined : appliedFilters.success,
        startTime: appliedFilters.startTime || undefined,
        endTime: appliedFilters.endTime || undefined,
      })
      setList(data.list)
      setTotal(data.total)
      setSelectedIds([])
    } catch (e) {
      setError(e instanceof Error ? e.message : '加载失败')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void loadData()
  }, [canView, canList, page, pageSize, appliedFilters])

  const totalPages = Math.max(1, Math.ceil(total / pageSize))

  useEffect(() => {
    if (page > totalPages) setPage(totalPages)
  }, [page, totalPages])

  const pageNumbers = useMemo(() => getPageNumbers(page, totalPages), [page, totalPages])

  const allSelected = list.length > 0 && selectedIds.length === list.length

  function toggleAll(checked: boolean) {
    if (checked) {
      setSelectedIds(list.map((item) => item.id))
    } else {
      setSelectedIds([])
    }
  }

  function toggleOne(id: number, checked: boolean) {
    setSelectedIds((prev) => {
      if (checked) return Array.from(new Set([...prev, id]))
      return prev.filter((item) => item !== id)
    })
  }

  function handleSearch() {
    setPage(1)
    setAppliedFilters(filters)
  }

  function handleReset() {
    setFilters(defaultFilters)
    setAppliedFilters(defaultFilters)
    setPage(1)
  }

  async function handleDeleteSelected() {
    if (selectedIds.length === 0) return
    if (!window.confirm(`确认删除选中的 ${selectedIds.length} 条登录日志吗？`)) return
    await loginLogApi.clear(selectedIds)
    await loadData()
  }

  async function handleClearAll() {
    if (!window.confirm('确认清空全部登录日志吗？')) return
    await loginLogApi.clear()
    await loadData()
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有登录日志页面权限。
          </div>
        ) : (
          <div className='space-y-4'>
            <div>
              <h2 className='text-2xl font-bold tracking-tight'>登录日志</h2>
              <p className='text-muted-foreground'>查看后台用户登录、退出和失败记录。</p>
            </div>

            <div className='grid grid-cols-1 gap-3 md:grid-cols-5'>
              <Input
                placeholder='登录地址'
                value={filters.ip}
                onChange={(e) => setFilters((v) => ({ ...v, ip: e.target.value }))}
              />
              <Input
                placeholder='用户名'
                value={filters.username}
                onChange={(e) => setFilters((v) => ({ ...v, username: e.target.value }))}
              />
              <DictSelect
                dictCode='log_status'
                placeholder='登录状态'
                value={filters.success}
                allowAll
                allLabel='全部状态'
                onValueChange={(value) => setFilters((v) => ({ ...v, success: value }))}
              />
              <div className='md:col-span-2'>
                <DateTimeRangePicker
                  placeholder='登录时间范围'
                  startTime={filters.startTime}
                  endTime={filters.endTime}
                  onChange={({ startTime, endTime }) =>
                    setFilters((v) => ({ ...v, startTime, endTime }))
                  }
                />
              </div>
            </div>

            <div className='flex flex-wrap items-center gap-2'>
              <Button onClick={handleSearch}>搜索</Button>
              <Button variant='outline' onClick={handleReset}>重置</Button>
              <div className='ms-auto flex items-center gap-2'>
                <Button
                  variant='destructive'
                  disabled={!canDelete || selectedIds.length === 0}
                  onClick={() => void handleDeleteSelected()}
                >
                  <Trash2 className='mr-1 h-4 w-4' />
                  删除
                </Button>
                <Button
                  variant='outline'
                  disabled={!canDelete}
                  onClick={() => void handleClearAll()}
                >
                  <XCircle className='mr-1 h-4 w-4' />
                  清空
                </Button>
              </div>
            </div>

            {error && <div className='text-sm text-red-600'>{error}</div>}

            <div className='overflow-hidden rounded-md border'>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className='w-10'>
                      <Checkbox
                        checked={allSelected}
                        onCheckedChange={(value) => toggleAll(!!value)}
                        aria-label='全选'
                      />
                    </TableHead>
                    <TableHead>用户名</TableHead>
                    <TableHead>设备类型</TableHead>
                    <TableHead>登录地址</TableHead>
                    <TableHead>登录地点</TableHead>
                    <TableHead>浏览器</TableHead>
                    <TableHead>操作系统</TableHead>
                    <TableHead>登录状态</TableHead>
                    <TableHead>操作信息</TableHead>
                    <TableHead>登录时间</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loading ? (
                    <TableRow>
                      <TableCell colSpan={10} className='h-20 text-center'>
                        加载中...
                      </TableCell>
                    </TableRow>
                  ) : list.length > 0 ? (
                    list.map((item) => (
                      <TableRow key={item.id}>
                        <TableCell>
                          <Checkbox
                            checked={selectedIds.includes(item.id)}
                            onCheckedChange={(value) => toggleOne(item.id, !!value)}
                            aria-label='选择当前行'
                          />
                        </TableCell>
                        <TableCell>{item.username || '-'}</TableCell>
                        <TableCell>{item.device || '-'}</TableCell>
                        <TableCell>{item.ip || '-'}</TableCell>
                        <TableCell>{item.location || '未知地点'}</TableCell>
                        <TableCell>{item.browser || '-'}</TableCell>
                        <TableCell>{item.os || '-'}</TableCell>
                        <TableCell>
                          <DictDisplay dictCode='log_status' value={String(item.success)} mode='badge' />
                        </TableCell>
                        <TableCell>{item.message || '-'}</TableCell>
                        <TableCell>{new Date(item.createdAt).toLocaleString()}</TableCell>
                      </TableRow>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={10} className='h-20 text-center'>
                        暂无数据
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>

            <div className='flex flex-wrap items-center justify-between gap-3'>
              <div className='text-sm text-muted-foreground'>共 {total} 条</div>

              <div className='flex items-center gap-2'>
                <Select
                  value={String(pageSize)}
                  onValueChange={(value) => {
                    setPageSize(Number(value))
                    setPage(1)
                  }}
                >
                  <SelectTrigger className='h-9 w-24'>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {[10, 20, 50, 100].map((size) => (
                      <SelectItem key={size} value={String(size)}>
                        {size} /页
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Button
                  variant='outline'
                  size='icon'
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={page <= 1}
                >
                  <ChevronLeft className='h-4 w-4' />
                </Button>

                {pageNumbers.map((item, index) =>
                  item === '...' ? (
                    <span key={`dot-${index}`} className='px-1 text-sm text-muted-foreground'>
                      ...
                    </span>
                  ) : (
                    <Button
                      key={`page-${item}`}
                      variant={page === item ? 'default' : 'outline'}
                      size='sm'
                      className='min-w-8'
                      onClick={() => setPage(item as number)}
                    >
                      {item}
                    </Button>
                  )
                )}

                <Button
                  variant='outline'
                  size='icon'
                  onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                  disabled={page >= totalPages}
                >
                  <ChevronRight className='h-4 w-4' />
                </Button>
              </div>
            </div>
          </div>
        )}
      </Main>
    </>
  )
}
