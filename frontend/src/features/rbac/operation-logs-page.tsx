import { useEffect, useMemo, useState } from 'react'
import { ChevronLeft, ChevronRight, Eye, Trash2, XCircle } from 'lucide-react'
import { operationLogApi } from '@/api/operation-log'
import type {
  OperationLogDetail,
  OperationLogRow,
} from '@/api/operation-log/types'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { DictDisplay } from '@/components/dict-display'
import { DictSelect } from '@/components/dict-select'
import { DateTimeRangePicker } from '@/components/date-time-range-picker'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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

const defaultFilters = {
  path: '',
  module: '',
  username: '',
  success: 'all',
  startTime: '',
  endTime: '',
}

function prettyJson(input?: string | null) {
  if (!input) return '-'
  try {
    return JSON.stringify(JSON.parse(input), null, 2)
  } catch {
    return input
  }
}

export function OperationLogsPage() {
  const { auth } = useAuthStore()
  const canView = auth.permissions.includes('system:operation-log:page')
  const canList = auth.permissions.includes('system:operation-log:list')
  const canDelete = auth.permissions.includes('system:operation-log:delete')

  const [filters, setFilters] = useState(defaultFilters)
  const [appliedFilters, setAppliedFilters] = useState(defaultFilters)

  const [list, setList] = useState<OperationLogRow[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [total, setTotal] = useState(0)

  const [selectedIds, setSelectedIds] = useState<number[]>([])

  const [detailOpen, setDetailOpen] = useState(false)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailData, setDetailData] = useState<OperationLogDetail | null>(null)

  async function loadData() {
    if (!canView || !canList) return
    setLoading(true)
    setError('')

    try {
      const data = await operationLogApi.list({
        page,
        pageSize,
        path: appliedFilters.path || undefined,
        module: appliedFilters.module || undefined,
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
    if (!window.confirm(`确认删除选中的 ${selectedIds.length} 条操作日志吗？`)) return
    await operationLogApi.clear(selectedIds)
    await loadData()
  }

  async function handleClearAll() {
    if (!window.confirm('确认清空全部操作日志吗？')) return
    await operationLogApi.clear()
    await loadData()
  }

  async function openDetail(id: number) {
    setDetailOpen(true)
    setDetailLoading(true)
    setDetailData(null)

    try {
      const data = await operationLogApi.detail(id)
      setDetailData(data)
    } catch (e) {
      setError(e instanceof Error ? e.message : '加载详情失败')
      setDetailOpen(false)
    } finally {
      setDetailLoading(false)
    }
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有操作日志页面权限。
          </div>
        ) : (
          <div className='space-y-4'>
            <div>
              <h2 className='text-2xl font-bold tracking-tight'>操作日志</h2>
              <p className='text-muted-foreground'>查看接口调用、操作人和执行结果。</p>
            </div>

            <div className='grid grid-cols-1 gap-3 md:grid-cols-6'>
              <Input
                placeholder='操作地址'
                value={filters.path}
                onChange={(e) => setFilters((v) => ({ ...v, path: e.target.value }))}
              />
              <Input
                placeholder='系统模块'
                value={filters.module}
                onChange={(e) => setFilters((v) => ({ ...v, module: e.target.value }))}
              />
              <Input
                placeholder='操作人员'
                value={filters.username}
                onChange={(e) => setFilters((v) => ({ ...v, username: e.target.value }))}
              />
              <DictSelect
                dictCode='log_status'
                placeholder='操作状态'
                value={filters.success}
                allowAll
                allLabel='全部状态'
                onValueChange={(value) => setFilters((v) => ({ ...v, success: value }))}
              />
              <div className='md:col-span-2'>
                <DateTimeRangePicker
                  placeholder='操作时间范围'
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
                    <TableHead>系统模块</TableHead>
                    <TableHead>操作类型</TableHead>
                    <TableHead>操作人员</TableHead>
                    <TableHead>操作地址</TableHead>
                    <TableHead>操作地点</TableHead>
                    <TableHead>操作状态</TableHead>
                    <TableHead>操作日期</TableHead>
                    <TableHead>消耗时间</TableHead>
                    <TableHead className='text-right'>操作</TableHead>
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
                        <TableCell>{item.module || '-'}</TableCell>
                        <TableCell>{item.action || item.method}</TableCell>
                        <TableCell>{item.username || '-'}</TableCell>
                        <TableCell className='max-w-[220px] truncate' title={item.path}>
                          {item.path}
                        </TableCell>
                        <TableCell>{item.location || '未知地点'}</TableCell>
                        <TableCell>
                          <DictDisplay dictCode='log_status' value={String(item.success)} mode='badge' />
                        </TableCell>
                        <TableCell>{new Date(item.createdAt).toLocaleString()}</TableCell>
                        <TableCell>{item.durationMs != null ? `${item.durationMs}ms` : '-'}</TableCell>
                        <TableCell className='text-right'>
                          <Button variant='ghost' size='sm' onClick={() => void openDetail(item.id)}>
                            <Eye className='mr-1 h-4 w-4' />详情
                          </Button>
                        </TableCell>
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

        <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
          <DialogContent className='max-h-[85vh] max-w-3xl overflow-y-auto'>
            <DialogHeader>
              <DialogTitle>详情</DialogTitle>
            </DialogHeader>
            {detailLoading ? (
              <div className='py-8 text-center text-sm text-muted-foreground'>加载中...</div>
            ) : detailData ? (
              <div className='overflow-hidden rounded-md border'>
                <Table>
                  <TableBody>
                    <TableRow>
                      <TableCell className='w-28 bg-muted/40 font-medium'>操作模块</TableCell>
                      <TableCell>{detailData.module || '-'}</TableCell>
                      <TableCell className='w-28 bg-muted/40 font-medium'>请求地址</TableCell>
                      <TableCell>{detailData.path}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>请求方式</TableCell>
                      <TableCell>{detailData.method}</TableCell>
                      <TableCell className='bg-muted/40 font-medium'>操作时间</TableCell>
                      <TableCell>{new Date(detailData.createdAt).toLocaleString()}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>登录信息</TableCell>
                      <TableCell colSpan={3}>
                        {detailData.username || '-'} / {detailData.ip || '-'} / {detailData.location || '未知地点'}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>操作方法</TableCell>
                      <TableCell colSpan={3}>{detailData.action || '-'}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>请求参数</TableCell>
                      <TableCell colSpan={3}>
                        <pre className='max-h-40 overflow-auto whitespace-pre-wrap break-all text-xs'>
                          {prettyJson(detailData.requestBody)}
                        </pre>
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>返回参数</TableCell>
                      <TableCell colSpan={3}>
                        <pre className='max-h-40 overflow-auto whitespace-pre-wrap break-all text-xs'>
                          {prettyJson(detailData.responseBody)}
                        </pre>
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className='bg-muted/40 font-medium'>操作状态</TableCell>
                      <TableCell>
                        <DictDisplay dictCode='log_status' value={String(detailData.success)} mode='badge' />
                      </TableCell>
                      <TableCell className='bg-muted/40 font-medium'>消耗时间</TableCell>
                      <TableCell>{detailData.durationMs != null ? `${detailData.durationMs}ms` : '-'}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            ) : (
              <div className='py-8 text-center text-sm text-muted-foreground'>暂无详情数据</div>
            )}
          </DialogContent>
        </Dialog>
      </Main>
    </>
  )
}
