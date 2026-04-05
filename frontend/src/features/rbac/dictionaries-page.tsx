import { useEffect, useMemo, useState } from 'react'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { dictionaryApi } from '@/api/dictionary'
import type { DictItemRow, DictTypeRow } from '@/api/dictionary/types'
import { Main } from '@/components/layout/main'
import { DictDisplay } from '@/components/dict-display'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useAuthStore } from '@/stores/auth-store'
import { PermissionGate } from './permission-gate'

const emptyTypeForm = {
  name: '',
  code: '',
  description: '',
  status: true,
  sort: 0,
}

const emptyItemForm = {
  label: '',
  value: '',
  tagType: 'neutral',
  tagClass: '',
  status: true,
  sort: 0,
}

export function DictionariesPage() {
  const { auth } = useAuthStore()
  const [types, setTypes] = useState<DictTypeRow[]>([])
  const [items, setItems] = useState<DictItemRow[]>([])
  const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null)
  const [loadingTypes, setLoadingTypes] = useState(false)
  const [loadingItems, setLoadingItems] = useState(false)
  const [error, setError] = useState('')

  const [typeKeyword, setTypeKeyword] = useState('')
  const [itemKeyword, setItemKeyword] = useState('')

  const [typeDialogOpen, setTypeDialogOpen] = useState(false)
  const [editingType, setEditingType] = useState<DictTypeRow | null>(null)
  const [typeForm, setTypeForm] = useState(emptyTypeForm)

  const [itemDialogOpen, setItemDialogOpen] = useState(false)
  const [editingItem, setEditingItem] = useState<DictItemRow | null>(null)
  const [itemForm, setItemForm] = useState(emptyItemForm)

  const canView = auth.permissions.includes('system:dict:page')
  const canAdd = auth.permissions.includes('system:dict:add')
  const canEdit = auth.permissions.includes('system:dict:edit')
  const canDelete = auth.permissions.includes('system:dict:delete')

  const selectedType = useMemo(
    () => types.find((it) => it.id === selectedTypeId) || null,
    [types, selectedTypeId]
  )

  async function loadTypes(keyword?: string) {
    setLoadingTypes(true)
    try {
      const list = await dictionaryApi.listTypes(keyword)
      setTypes(list)
      setSelectedTypeId((prev) => {
        if (prev && list.some((it) => it.id === prev)) return prev
        return list[0]?.id ?? null
      })
    } catch (e) {
      setError(e instanceof Error ? e.message : '加载字典类型失败')
    } finally {
      setLoadingTypes(false)
    }
  }

  async function loadItems(typeId: number, keyword?: string) {
    setLoadingItems(true)
    try {
      const list = await dictionaryApi.listItems(typeId, keyword)
      setItems(list)
    } catch (e) {
      setError(e instanceof Error ? e.message : '加载字典项失败')
    } finally {
      setLoadingItems(false)
    }
  }

  useEffect(() => {
    if (!canView) return
    void loadTypes()
  }, [canView])

  useEffect(() => {
    if (!selectedTypeId) {
      setItems([])
      return
    }
    void loadItems(selectedTypeId)
  }, [selectedTypeId])

  async function applyTypeSearch() {
    await loadTypes(typeKeyword.trim() || undefined)
  }

  async function applyItemSearch() {
    if (!selectedTypeId) return
    await loadItems(selectedTypeId, itemKeyword.trim() || undefined)
  }

  function openCreateType() {
    setEditingType(null)
    setTypeForm(emptyTypeForm)
    setTypeDialogOpen(true)
  }

  function openEditType(row: DictTypeRow) {
    setEditingType(row)
    setTypeForm({
      name: row.name,
      code: row.code,
      description: row.description || '',
      status: row.status,
      sort: row.sort,
    })
    setTypeDialogOpen(true)
  }

  async function submitType() {
    if (!typeForm.name.trim() || !typeForm.code.trim()) {
      window.alert('请填写字典名称和编码')
      return
    }

    const payload = {
      name: typeForm.name.trim(),
      code: typeForm.code.trim(),
      description: typeForm.description.trim() || null,
      status: typeForm.status,
      sort: Number(typeForm.sort || 0),
    }

    if (editingType) {
      await dictionaryApi.updateType(editingType.id, payload)
    } else {
      await dictionaryApi.createType(payload)
    }

    setTypeDialogOpen(false)
    setTypeForm(emptyTypeForm)
    await loadTypes(typeKeyword.trim() || undefined)
  }

  async function removeType(id: number) {
    if (!window.confirm('确认删除该字典类型吗？会同时删除其字典项。')) return
    await dictionaryApi.deleteType(id)
    await loadTypes(typeKeyword.trim() || undefined)
  }

  function openCreateItem() {
    if (!selectedTypeId) return
    setEditingItem(null)
    setItemForm(emptyItemForm)
    setItemDialogOpen(true)
  }

  function openEditItem(row: DictItemRow) {
    setEditingItem(row)
    setItemForm({
      label: row.label,
      value: row.value,
      tagType: row.tagType || 'neutral',
      tagClass: row.tagClass || '',
      status: row.status,
      sort: row.sort,
    })
    setItemDialogOpen(true)
  }

  async function submitItem() {
    if (!selectedTypeId) return
    if (!itemForm.label.trim() || !itemForm.value.trim()) {
      window.alert('请填写字典标签和值')
      return
    }

    const payload = {
      label: itemForm.label.trim(),
      value: itemForm.value.trim(),
      tagType: itemForm.tagType || null,
      tagClass: itemForm.tagClass.trim() || null,
      status: itemForm.status,
      sort: Number(itemForm.sort || 0),
    }

    if (editingItem) {
      await dictionaryApi.updateItem(editingItem.id, payload)
    } else {
      await dictionaryApi.createItem(selectedTypeId, payload)
    }

    setItemDialogOpen(false)
    setItemForm(emptyItemForm)
    await loadItems(selectedTypeId, itemKeyword.trim() || undefined)
  }

  async function removeItem(id: number) {
    if (!selectedTypeId) return
    if (!window.confirm('确认删除该字典项吗？')) return
    await dictionaryApi.deleteItem(id)
    await loadItems(selectedTypeId, itemKeyword.trim() || undefined)
  }

  return (
    <>
      <Main className='space-y-4 px-4 py-5'>
        {!canView ? (
          <div className='rounded-md border p-4 text-sm text-muted-foreground'>
            当前账号没有字典管理页面权限。
          </div>
        ) : (
          <>
            <div>
              <h2 className='text-2xl font-bold tracking-tight'>字典管理</h2>
              <p className='text-muted-foreground'>维护系统字典类型和字典数据项。</p>
            </div>

            {error && <p className='text-sm text-red-600'>{error}</p>}

            <div className='grid gap-4 lg:grid-cols-[320px_1fr]'>
              <div className='space-y-3 rounded-md border p-3'>
                <div className='flex gap-2'>
                  <Input
                    placeholder='搜索字典名称/编码'
                    value={typeKeyword}
                    onChange={(e) => setTypeKeyword(e.target.value)}
                  />
                  <Button variant='outline' onClick={() => void applyTypeSearch()}>
                    搜索
                  </Button>
                </div>
                <div className='flex gap-2'>
                  <PermissionGate permission='system:dict:add'>
                    <Button size='sm' onClick={openCreateType}>
                      <Plus className='mr-1 h-4 w-4' />
                      新增
                    </Button>
                  </PermissionGate>
                </div>
                <div className='max-h-[520px] space-y-1 overflow-auto'>
                  {loadingTypes ? (
                    <div className='text-sm text-muted-foreground'>加载中...</div>
                  ) : types.length > 0 ? (
                    types.map((it) => (
                      <button
                        key={it.id}
                        type='button'
                        onClick={() => setSelectedTypeId(it.id)}
                        className={`w-full rounded-md border px-3 py-2 text-left ${
                          selectedTypeId === it.id
                            ? 'border-primary bg-primary/5'
                            : 'hover:bg-muted/50'
                        }`}
                      >
                        <div className='font-medium'>{it.name}</div>
                        <div className='text-xs text-muted-foreground'>{it.code}</div>
                      </button>
                    ))
                  ) : (
                    <div className='text-sm text-muted-foreground'>暂无字典类型</div>
                  )}
                </div>
              </div>

              <div className='space-y-3 rounded-md border p-3'>
                <div className='flex flex-wrap items-center justify-between gap-2'>
                  <div>
                    <div className='text-lg font-semibold'>
                      {selectedType?.name || '请选择字典类型'}
                    </div>
                    <div className='text-xs text-muted-foreground'>
                      {selectedType?.code || '-'}
                    </div>
                  </div>
                  {selectedType && (
                    <div className='flex gap-2'>
                      <PermissionGate permission='system:dict:edit'>
                        <Button
                          variant='outline'
                          size='sm'
                          disabled={!canEdit}
                          onClick={() => openEditType(selectedType)}
                        >
                          编辑类型
                        </Button>
                      </PermissionGate>
                      <PermissionGate permission='system:dict:delete'>
                        <Button
                          variant='destructive'
                          size='sm'
                          disabled={!canDelete}
                          onClick={() => void removeType(selectedType.id)}
                        >
                          删除类型
                        </Button>
                      </PermissionGate>
                    </div>
                  )}
                </div>

                <div className='flex flex-wrap gap-2'>
                  <Input
                    className='max-w-xs'
                    placeholder='搜索字典标签/值'
                    value={itemKeyword}
                    onChange={(e) => setItemKeyword(e.target.value)}
                    disabled={!selectedTypeId}
                  />
                  <Button
                    variant='outline'
                    disabled={!selectedTypeId}
                    onClick={() => void applyItemSearch()}
                  >
                    搜索
                  </Button>
                  <PermissionGate permission='system:dict:add'>
                    <Button disabled={!selectedTypeId || !canAdd} onClick={openCreateItem}>
                      <Plus className='mr-1 h-4 w-4' />
                      新增字典项
                    </Button>
                  </PermissionGate>
                </div>

                <div className='overflow-hidden rounded-md border'>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>字典标签</TableHead>
                        <TableHead>字典值</TableHead>
                        <TableHead>状态</TableHead>
                        <TableHead>显示样式</TableHead>
                        <TableHead>排序</TableHead>
                        <TableHead className='text-right'>操作</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {loadingItems ? (
                        <TableRow>
                          <TableCell className='h-24 text-center' colSpan={6}>
                            加载中...
                          </TableCell>
                        </TableRow>
                      ) : items.length > 0 ? (
                        items.map((item) => (
                          <TableRow key={item.id}>
                            <TableCell>{item.label}</TableCell>
                            <TableCell>{item.value}</TableCell>
                            <TableCell>
                              <DictDisplay
                                dictCode='menu_visible'
                                value={item.status ? '1' : '0'}
                                mode='badge'
                              />
                            </TableCell>
                            <TableCell>
                              <DictDisplay
                                dictCode={selectedType?.code || ''}
                                value={item.value}
                                mode='badge'
                              />
                            </TableCell>
                            <TableCell>{item.sort}</TableCell>
                            <TableCell className='text-right'>
                              <DropdownMenu modal={false}>
                                <DropdownMenuTrigger asChild>
                                  <Button variant='ghost' className='h-8 w-8 p-0'>
                                    <DotsHorizontalIcon className='h-4 w-4' />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align='end' className='w-[160px]'>
                                  <DropdownMenuItem
                                    disabled={!canEdit}
                                    onClick={() => openEditItem(item)}
                                  >
                                    编辑
                                    <DropdownMenuShortcut>
                                      <Pencil className='h-4 w-4' />
                                    </DropdownMenuShortcut>
                                  </DropdownMenuItem>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem
                                    variant='destructive'
                                    disabled={!canDelete}
                                    onClick={() => void removeItem(item.id)}
                                  >
                                    删除
                                    <DropdownMenuShortcut>
                                      <Trash2 className='h-4 w-4' />
                                    </DropdownMenuShortcut>
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </TableCell>
                          </TableRow>
                        ))
                      ) : (
                        <TableRow>
                          <TableCell className='h-24 text-center' colSpan={6}>
                            暂无字典项
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>
            </div>
          </>
        )}

        <Dialog open={typeDialogOpen} onOpenChange={setTypeDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingType ? '编辑字典类型' : '新增字典类型'}</DialogTitle>
            </DialogHeader>
            <div className='grid grid-cols-1 gap-3 md:grid-cols-2'>
              <div className='space-y-1'>
                <label className='text-sm'>字典名称</label>
                <Input
                  value={typeForm.name}
                  onChange={(e) => setTypeForm((f) => ({ ...f, name: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>字典编码</label>
                <Input
                  value={typeForm.code}
                  onChange={(e) => setTypeForm((f) => ({ ...f, code: e.target.value }))}
                />
              </div>
              <div className='space-y-1 md:col-span-2'>
                <label className='text-sm'>描述</label>
                <Input
                  value={typeForm.description}
                  onChange={(e) =>
                    setTypeForm((f) => ({ ...f, description: e.target.value }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>排序</label>
                <Input
                  type='number'
                  value={typeForm.sort}
                  onChange={(e) =>
                    setTypeForm((f) => ({ ...f, sort: Number(e.target.value) || 0 }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>状态</label>
                <Select
                  value={typeForm.status ? '1' : '0'}
                  onValueChange={(value) =>
                    setTypeForm((f) => ({ ...f, status: value === '1' }))
                  }
                >
                  <SelectTrigger className='w-full'>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='1'>启用</SelectItem>
                    <SelectItem value='0'>停用</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <DialogFooter>
              <Button variant='outline' onClick={() => setTypeDialogOpen(false)}>
                取消
              </Button>
              <Button onClick={() => void submitType()}>保存</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <Dialog open={itemDialogOpen} onOpenChange={setItemDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingItem ? '编辑字典项' : '新增字典项'}</DialogTitle>
            </DialogHeader>
            <div className='grid grid-cols-1 gap-3 md:grid-cols-2'>
              <div className='space-y-1'>
                <label className='text-sm'>字典标签</label>
                <Input
                  value={itemForm.label}
                  onChange={(e) => setItemForm((f) => ({ ...f, label: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>字典值</label>
                <Input
                  value={itemForm.value}
                  onChange={(e) => setItemForm((f) => ({ ...f, value: e.target.value }))}
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>标签类型</label>
                <Select
                  value={itemForm.tagType}
                  onValueChange={(value) => setItemForm((f) => ({ ...f, tagType: value }))}
                >
                  <SelectTrigger className='w-full'>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='neutral'>灰色</SelectItem>
                    <SelectItem value='info'>蓝色</SelectItem>
                    <SelectItem value='success'>绿色</SelectItem>
                    <SelectItem value='warning'>橙色</SelectItem>
                    <SelectItem value='danger'>红色</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>自定义样式类（可选）</label>
                <Input
                  value={itemForm.tagClass}
                  onChange={(e) =>
                    setItemForm((f) => ({ ...f, tagClass: e.target.value }))
                  }
                  placeholder='如 border-purple-200 bg-purple-50 text-purple-700'
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>排序</label>
                <Input
                  type='number'
                  value={itemForm.sort}
                  onChange={(e) =>
                    setItemForm((f) => ({ ...f, sort: Number(e.target.value) || 0 }))
                  }
                />
              </div>
              <div className='space-y-1'>
                <label className='text-sm'>状态</label>
                <Select
                  value={itemForm.status ? '1' : '0'}
                  onValueChange={(value) =>
                    setItemForm((f) => ({ ...f, status: value === '1' }))
                  }
                >
                  <SelectTrigger className='w-full'>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='1'>启用</SelectItem>
                    <SelectItem value='0'>停用</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <DialogFooter>
              <Button variant='outline' onClick={() => setItemDialogOpen(false)}>
                取消
              </Button>
              <Button onClick={() => void submitItem()}>保存</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </Main>
    </>
  )
}
