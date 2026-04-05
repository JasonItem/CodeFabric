import { useEffect, useMemo, useRef, useState } from 'react'
import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { PhotoSlider } from 'react-photo-view'
import { toast } from 'sonner'
import {
  ChevronDown,
  ChevronRight,
  FolderOpen,
  PanelLeft,
  Plus,
  Search,
  Trash2,
  Upload,
} from 'lucide-react'
import { fileApi } from '@/api/file'
import type { FileFolder, FileKind, FileSource, StoredFile } from '@/api/file/types'
import { DictDisplay } from '@/components/dict-display'
import { Main } from '@/components/layout/main'
import { ConfirmDialog } from '@/components/confirm-dialog'
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
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useIsMobile } from '@/hooks/use-mobile'
import { useAuthStore } from '@/stores/auth-store'
import { formatFileSize, getFileIcon } from '@/lib/file-utils'

type FolderNode = FileFolder & { children: FolderNode[] }

const pageSize = 30

function buildFolderTree(folders: FileFolder[]) {
  const map = new Map<number, FolderNode>()
  const roots: FolderNode[] = []

  folders
    .slice()
    .sort((a, b) => a.sort - b.sort || a.id - b.id)
    .forEach((folder) => map.set(folder.id, { ...folder, children: [] }))

  map.forEach((node) => {
    if (node.parentId && map.has(node.parentId)) {
      map.get(node.parentId)!.children.push(node)
    } else {
      roots.push(node)
    }
  })

  return roots
}

function formatDate(value: string) {
  return new Date(value).toLocaleString()
}

export function FilesPage() {
  const isMobile = useIsMobile()
  const { auth } = useAuthStore()
  const canView = auth.permissions.includes('system:file:page')
  const canList = auth.permissions.includes('system:file:list')
  const canUpload = auth.permissions.includes('system:file:upload')
  const canEdit = auth.permissions.includes('system:file:edit')
  const canDelete = auth.permissions.includes('system:file:delete')
  const canFolder = auth.permissions.includes('system:file:folder')

  const uploadRef = useRef<HTMLInputElement | null>(null)

  const [folders, setFolders] = useState<FileFolder[]>([])
  const [files, setFiles] = useState<StoredFile[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [folderId, setFolderId] = useState<number | null>(null)
  const [expanded, setExpanded] = useState<Set<number>>(new Set())
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set())

  const [keyword, setKeyword] = useState('')
  const [searchKeyword, setSearchKeyword] = useState('')
  const [source, setSource] = useState<FileSource | ''>('')
  const [kind, setKind] = useState<FileKind | ''>('')
  const [createFolderOpen, setCreateFolderOpen] = useState(false)
  const [newFolderName, setNewFolderName] = useState('')
  const [renameFolderOpen, setRenameFolderOpen] = useState(false)
  const [renameFolderValue, setRenameFolderValue] = useState('')
  const [renameTargetFolder, setRenameTargetFolder] = useState<FileFolder | null>(null)
  const [renameOpen, setRenameOpen] = useState(false)
  const [moveOpen, setMoveOpen] = useState(false)
  const [deleteFolderOpen, setDeleteFolderOpen] = useState(false)
  const [deleteFileOpen, setDeleteFileOpen] = useState(false)
  const [deleteTargetFile, setDeleteTargetFile] = useState<StoredFile | null>(null)
  const [activeFile, setActiveFile] = useState<StoredFile | null>(null)
  const [renameValue, setRenameValue] = useState('')
  const [moveFolderValue, setMoveFolderValue] = useState<string>('ROOT')
  const [deleteFolderValue, setDeleteFolderValue] = useState<string>('ROOT')
  const [activeFolder, setActiveFolder] = useState<FileFolder | null>(null)
  const [previewVisible, setPreviewVisible] = useState(false)
  const [previewIndex, setPreviewIndex] = useState(0)
  const [folderPanelOpen, setFolderPanelOpen] = useState(false)

  const folderTree = useMemo(() => buildFolderTree(folders), [folders])
  const imageFiles = useMemo(() => files.filter((item) => item.kind === 'IMAGE'), [files])

  async function loadFolders() {
    if (!canList) return
    const list = await fileApi.listFolders()
    setFolders(list)
    setExpanded(new Set(list.map((f) => f.id)))
  }

  async function loadFiles(targetPage = page) {
    if (!canList) return
    setLoading(true)
    try {
      const result = await fileApi.listFiles({
        page: targetPage,
        pageSize,
        folderId,
        keyword: searchKeyword || undefined,
        source,
        kind,
      })
      setFiles(result.list)
      setTotal(result.total)
      setPage(result.page)
      setSelectedIds(new Set())
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '加载失败')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    if (!canView) return
    void loadFolders()
  }, [canView, canList])

  useEffect(() => {
    if (!canView) return
    void loadFiles(1)
  }, [canView, folderId, searchKeyword, source, kind])

  function toggleFolderExpand(id: number) {
    setExpanded((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  async function handleUpload(files: FileList | null) {
    if (!files?.length) return
    if (!canUpload) return
    try {
      await fileApi.uploadFiles({
        files: Array.from(files),
        folderId,
        source: 'ADMIN',
      })
      await loadFiles(1)
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '上传失败')
    }
  }

  async function handleCreateFolder() {
    if (!canFolder) return
    setNewFolderName('')
    setCreateFolderOpen(true)
  }

  async function submitCreateFolder() {
    const name = newFolderName.trim()
    if (!name) return
    await fileApi.createFolder({
      name,
      parentId: folderId,
    })
    setCreateFolderOpen(false)
    await loadFolders()
  }

  async function handleRenameFolder(folder: FileFolder) {
    if (!canFolder) return
    setRenameTargetFolder(folder)
    setRenameFolderValue(folder.name)
    setRenameFolderOpen(true)
  }

  async function submitRenameFolder() {
    if (!renameTargetFolder) return
    const name = renameFolderValue.trim()
    if (!name) return
    try {
      await fileApi.updateFolder(renameTargetFolder.id, { name })
      setRenameFolderOpen(false)
      setRenameTargetFolder(null)
      await loadFolders()
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '重命名分组失败')
    }
  }

  async function handleDeleteFolder(folder: FileFolder) {
    if (!canFolder) return
    try {
      const check = await fileApi.listFiles({
        page: 1,
        pageSize: 1,
        folderId: folder.id,
      })
      if (check.total > 0) {
        setActiveFolder(folder)
        setDeleteFolderValue('ROOT')
        setDeleteFolderOpen(true)
        return
      }
      if (!window.confirm(`确认删除分组「${folder.name}」吗？`)) return
      await fileApi.removeFolder(folder.id)
      if (folderId === folder.id) setFolderId(null)
      await loadFolders()
      await loadFiles(1)
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '删除分组失败')
    }
  }

  async function submitDeleteFolderWithMove() {
    if (!activeFolder) return
    try {
      const moveTo = deleteFolderValue === 'ROOT' ? null : Number(deleteFolderValue)
      await fileApi.removeFolder(activeFolder.id, moveTo)
      setDeleteFolderOpen(false)
      if (folderId === activeFolder.id) setFolderId(null)
      setActiveFolder(null)
      await loadFolders()
      await loadFiles(1)
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '删除分组失败')
    }
  }

  async function submitRenameFile() {
    if (!canEdit || !activeFile) return
    const name = renameValue.trim()
    if (!name) return
    await fileApi.updateFile(activeFile.id, { name })
    setRenameOpen(false)
    setActiveFile(null)
    await loadFiles(page)
  }

  function openRenameFile(file: StoredFile) {
    if (!canEdit) return
    setActiveFile(file)
    setRenameValue(file.name)
    setRenameOpen(true)
  }

  async function submitMoveFile() {
    if (!canEdit || !activeFile) return
    const folderId = moveFolderValue === 'ROOT' ? null : Number(moveFolderValue)
    await fileApi.updateFile(activeFile.id, { folderId })
    setMoveOpen(false)
    setActiveFile(null)
    await loadFiles(page)
  }

  function openMoveFile(file: StoredFile) {
    if (!canEdit) return
    setActiveFile(file)
    setMoveFolderValue(file.folderId ? String(file.folderId) : 'ROOT')
    setMoveOpen(true)
  }

  function openPreviewFile(file: StoredFile) {
    if (file.kind === 'VIDEO') {
      window.open(file.url, '_blank', 'noopener,noreferrer')
      return
    }
    if (file.kind !== 'IMAGE') return
    const index = Math.max(
      imageFiles.findIndex((item) => item.id === file.id),
      0
    )
    setPreviewIndex(index)
    setPreviewVisible(true)
  }

  async function handleDeleteFile(file: StoredFile) {
    if (!canDelete) return
    setDeleteTargetFile(file)
    setDeleteFileOpen(true)
  }

  async function submitDeleteFile() {
    if (!deleteTargetFile) return
    try {
      await fileApi.removeFile(deleteTargetFile.id)
      setDeleteFileOpen(false)
      setDeleteTargetFile(null)
      await loadFiles(page)
    } catch (e) {
      toast.error(e instanceof Error ? e.message : '删除文件失败')
    }
  }

  async function handleBatchDelete() {
    if (!canDelete || selectedIds.size === 0) return
    if (!window.confirm(`确认删除选中的 ${selectedIds.size} 个文件吗？`)) return
    await fileApi.removeFiles(Array.from(selectedIds))
    await loadFiles(1)
  }

  function toggleSelect(fileId: number) {
    setSelectedIds((prev) => {
      const next = new Set(prev)
      if (next.has(fileId)) next.delete(fileId)
      else next.add(fileId)
      return next
    })
  }

  function renderFolder(nodes: FolderNode[], depth = 0): React.ReactNode {
    return nodes.map((node) => {
      const hasChildren = node.children.length > 0
      const isExpanded = expanded.has(node.id)
      const active = folderId === node.id
      return (
        <div key={node.id}>
          <div
            className={`group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm ${
              active ? 'bg-muted' : 'hover:bg-muted/60'
            }`}
            style={{ paddingLeft: `${8 + depth * 16}px` }}
          >
            {hasChildren ? (
              <button
                type='button'
                className='rounded p-0.5 hover:bg-muted'
                onClick={() => toggleFolderExpand(node.id)}
              >
                {isExpanded ? (
                  <ChevronDown className='h-4 w-4 text-muted-foreground' />
                ) : (
                  <ChevronRight className='h-4 w-4 text-muted-foreground' />
                )}
              </button>
            ) : (
              <span className='inline-block h-4 w-4' />
            )}

            <button
              type='button'
              className='flex min-w-0 flex-1 items-center gap-1 text-left'
              onClick={() => {
                setFolderId(node.id)
                if (isMobile) setFolderPanelOpen(false)
              }}
            >
              <FolderOpen className='h-4 w-4 text-muted-foreground' />
              <span className='truncate'>{node.name}</span>
            </button>

            {canFolder && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant='ghost'
                    size='icon'
                    className='h-6 w-6 opacity-0 group-hover:opacity-100'
                  >
                    <DotsHorizontalIcon className='h-4 w-4' />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align='end'>
                  <DropdownMenuItem onClick={() => void handleRenameFolder(node)}>
                    重命名分组
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    className='text-destructive focus:text-destructive'
                    onClick={() => void handleDeleteFolder(node)}
                  >
                    删除分组
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
          {hasChildren && isExpanded && renderFolder(node.children, depth + 1)}
        </div>
      )
    })
  }

  function renderFolderPanel() {
    return (
      <>
        <div className='mb-2 flex items-center justify-between'>
          <Button
            variant={folderId === null ? 'secondary' : 'ghost'}
            className='h-8 flex-1 justify-start'
            onClick={() => {
              setFolderId(null)
              if (isMobile) setFolderPanelOpen(false)
            }}
          >
            <FolderOpen className='mr-2 h-4 w-4' />
            全部
          </Button>
          {canFolder && (
            <Button
              variant='outline'
              size='icon'
              className='ml-2 h-8 w-8'
              onClick={() => void handleCreateFolder()}
            >
              <Plus className='h-4 w-4' />
            </Button>
          )}
        </div>
        <ScrollArea className='h-[65vh] pr-2 md:h-[600px]'>{renderFolder(folderTree)}</ScrollArea>
      </>
    )
  }

  const folderOptions = useMemo(() => {
    const output: Array<{ id: number; label: string }> = []
    function walk(nodes: FolderNode[], depth: number) {
      nodes.forEach((node) => {
        output.push({
          id: node.id,
          label: `${'　'.repeat(depth)}${node.name}`,
        })
        if (node.children.length > 0) {
          walk(node.children, depth + 1)
        }
      })
    }
    walk(folderTree, 0)
    return output
  }, [folderTree])

  const deleteFolderOptions = useMemo(
    () => folderOptions.filter((item) => item.id !== activeFolder?.id),
    [folderOptions, activeFolder?.id]
  )

  return (
    <Main className='space-y-4 px-4 py-5'>
      {!canView ? (
        <div className='rounded-md border p-4 text-sm text-muted-foreground'>
          当前账号没有文件管理页面权限。
        </div>
      ) : (
        <>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>文件管理</h2>
            <p className='text-muted-foreground'>
              文件分组、上传、搜索与筛选统一管理。
            </p>
          </div>

          <div className='grid min-h-[680px] grid-cols-1 overflow-hidden rounded-md border md:grid-cols-[260px_1fr]'>
            <div className='hidden border-r bg-muted/10 p-3 md:block'>{renderFolderPanel()}</div>

            <div className='flex min-h-0 flex-col p-4'>
              <div className='flex flex-wrap items-center gap-2'>
                <Button
                  type='button'
                  variant='outline'
                  className='md:hidden'
                  onClick={() => setFolderPanelOpen(true)}
                >
                  <PanelLeft className='mr-1 h-4 w-4' />
                  分组
                </Button>
                <input
                  ref={uploadRef}
                  type='file'
                  className='hidden'
                  multiple
                  onChange={(e) => void handleUpload(e.target.files)}
                />
                <Button
                  onClick={() => uploadRef.current?.click()}
                  disabled={!canUpload}
                >
                  <Upload className='mr-1 h-4 w-4' />
                  本地上传
                </Button>
                <Button
                  variant='destructive'
                  disabled={!canDelete || selectedIds.size === 0}
                  onClick={() => void handleBatchDelete()}
                >
                  <Trash2 className='mr-1 h-4 w-4' />
                  批量删除
                </Button>
                <Select
                  value={source || 'ALL'}
                  onValueChange={(v) => setSource(v === 'ALL' ? '' : (v as FileSource))}
                >
                  <SelectTrigger className='w-[140px]'>
                    <SelectValue placeholder='文件来源' />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='ALL'>全部来源</SelectItem>
                    <SelectItem value='ADMIN'>后台上传</SelectItem>
                    <SelectItem value='USER'>用户端上传</SelectItem>
                  </SelectContent>
                </Select>
                <Select
                  value={kind || 'ALL'}
                  onValueChange={(v) => setKind(v === 'ALL' ? '' : (v as FileKind))}
                >
                  <SelectTrigger className='w-[120px]'>
                    <SelectValue placeholder='文件类型' />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='ALL'>全部类型</SelectItem>
                    <SelectItem value='IMAGE'>图片</SelectItem>
                    <SelectItem value='VIDEO'>视频</SelectItem>
                    <SelectItem value='FILE'>文件</SelectItem>
                  </SelectContent>
                </Select>
                <div className='ml-auto flex items-center gap-2'>
                  <Input
                    placeholder='搜索文件名'
                    value={keyword}
                    onChange={(e) => setKeyword(e.target.value)}
                    className='w-[220px]'
                  />
                  <Button onClick={() => setSearchKeyword(keyword)}>
                    <Search className='mr-1 h-4 w-4' />
                    搜索
                  </Button>
                </div>
              </div>

              <div className='mt-4 min-h-0 flex-1 overflow-y-auto pr-1'>
                <div className='grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6'>
                {loading ? (
                  <div className='col-span-full rounded-md border p-8 text-center text-sm text-muted-foreground'>
                    加载中...
                  </div>
                ) : files.length === 0 ? (
                  <div className='col-span-full rounded-md border p-8 text-center text-sm text-muted-foreground'>
                    暂无文件
                  </div>
                ) : (
                  files.map((file) => {
                    const FileIcon = getFileIcon(file)
                    const selected = selectedIds.has(file.id)
                    return (
                      <div
                        key={file.id}
                        className={`group rounded-md border p-2 transition-colors ${
                          selected ? 'border-primary bg-primary/5' : ''
                        }`}
                      >
                        <div
                          role='button'
                          tabIndex={0}
                          className='relative block h-32 w-full overflow-hidden rounded-md border bg-muted/20'
                          onClick={() => toggleSelect(file.id)}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                              e.preventDefault()
                              toggleSelect(file.id)
                            }
                          }}
                        >
                          {file.kind === 'IMAGE' ? (
                            <img
                              src={file.url}
                              alt={file.name}
                              className='h-full w-full object-cover'
                              loading='lazy'
                            />
                          ) : (
                            <div className='flex h-full w-full flex-col items-center justify-center gap-2 text-muted-foreground'>
                              <FileIcon className='h-8 w-8' />
                              <span className='text-xs uppercase'>{file.ext || 'file'}</span>
                            </div>
                          )}
                          <div className='absolute right-1 top-1 opacity-0 transition group-hover:opacity-100'>
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button
                                  variant='secondary'
                                  size='icon'
                                  className='h-7 w-7'
                                  onClick={(e) => e.stopPropagation()}
                                >
                                  <DotsHorizontalIcon className='h-4 w-4' />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align='end'>
                                <DropdownMenuItem
                                  disabled={!canEdit}
                                  onClick={() => openRenameFile(file)}
                                >
                                  重命名
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                  disabled={!canEdit}
                                  onClick={() => openMoveFile(file)}
                                >
                                  修改分组
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                  disabled={file.kind === 'FILE'}
                                  onClick={() => openPreviewFile(file)}
                                >
                                  预览
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <a href={file.url} target='_blank' rel='noreferrer'>
                                    查看地址
                                  </a>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                  disabled={!canDelete}
                                  className='text-destructive focus:text-destructive'
                                  onClick={() => void handleDeleteFile(file)}
                                >
                                  删除
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>

                        <div className='mt-2 space-y-1'>
                          <div className='truncate text-sm font-medium' title={file.name}>
                            {file.name}
                          </div>
                          <div className='flex items-center justify-between text-xs text-muted-foreground'>
                            <span>{formatFileSize(file.size)}</span>
                            <DictDisplay
                              dictCode='file_source'
                              value={file.source}
                              fallback={file.source === 'ADMIN' ? '后台上传' : '用户端上传'}
                              mode='badge'
                            />
                          </div>
                          <div className='truncate text-xs text-muted-foreground' title={formatDate(file.createdAt)}>
                            {formatDate(file.createdAt)}
                          </div>
                        </div>
                      </div>
                    )
                  })
                )}
                </div>
              </div>

              <div className='-mx-4 mt-3 flex items-center justify-between border-t px-4 pt-3 text-sm text-muted-foreground'>
                <span>共 {total} 条</span>
                <div className='flex items-center gap-2'>
                  <Button
                    variant='outline'
                    size='sm'
                    disabled={page <= 1}
                    onClick={() => void loadFiles(page - 1)}
                  >
                    上一页
                  </Button>
                  <span>
                    第 {page} / {Math.max(Math.ceil(total / pageSize), 1)} 页
                  </span>
                  <Button
                    variant='outline'
                    size='sm'
                    disabled={page >= Math.max(Math.ceil(total / pageSize), 1)}
                    onClick={() => void loadFiles(page + 1)}
                  >
                    下一页
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <Sheet open={folderPanelOpen} onOpenChange={setFolderPanelOpen}>
            <SheetContent side='left' className='w-[320px] p-0 sm:w-[360px]'>
              <SheetHeader className='border-b px-4 py-3'>
                <SheetTitle>文件分组</SheetTitle>
              </SheetHeader>
              <div className='p-3'>{renderFolderPanel()}</div>
            </SheetContent>
          </Sheet>

          <Dialog open={createFolderOpen} onOpenChange={setCreateFolderOpen}>
            <DialogContent className='sm:max-w-md'>
              <DialogHeader>
                <DialogTitle>新增分组</DialogTitle>
              </DialogHeader>
              <div className='space-y-2'>
                <label className='text-sm'>分组名称</label>
                <Input
                  value={newFolderName}
                  onChange={(e) => setNewFolderName(e.target.value)}
                  placeholder='请输入分组名称'
                />
              </div>
              <DialogFooter>
                <Button variant='outline' onClick={() => setCreateFolderOpen(false)}>
                  取消
                </Button>
                <Button onClick={() => void submitCreateFolder()} disabled={!newFolderName.trim()}>
                  保存
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Dialog
            open={renameFolderOpen}
            onOpenChange={(next) => {
              setRenameFolderOpen(next)
              if (!next) setRenameTargetFolder(null)
            }}
          >
            <DialogContent className='sm:max-w-md'>
              <DialogHeader>
                <DialogTitle>重命名分组</DialogTitle>
              </DialogHeader>
              <div className='space-y-2'>
                <label className='text-sm'>分组名称</label>
                <Input
                  value={renameFolderValue}
                  onChange={(e) => setRenameFolderValue(e.target.value)}
                  placeholder='请输入分组名称'
                />
              </div>
              <DialogFooter>
                <Button variant='outline' onClick={() => setRenameFolderOpen(false)}>
                  取消
                </Button>
                <Button onClick={() => void submitRenameFolder()} disabled={!renameFolderValue.trim()}>
                  保存
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Dialog open={renameOpen} onOpenChange={setRenameOpen}>
            <DialogContent className='sm:max-w-md'>
              <DialogHeader>
                <DialogTitle>重命名文件</DialogTitle>
              </DialogHeader>
              <div className='space-y-2'>
                <label className='text-sm'>文件名称</label>
                <Input
                  value={renameValue}
                  onChange={(e) => setRenameValue(e.target.value)}
                  placeholder='请输入文件名称'
                />
              </div>
              <DialogFooter>
                <Button variant='outline' onClick={() => setRenameOpen(false)}>
                  取消
                </Button>
                <Button onClick={() => void submitRenameFile()} disabled={!renameValue.trim()}>
                  保存
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Dialog open={moveOpen} onOpenChange={setMoveOpen}>
            <DialogContent className='sm:max-w-md'>
              <DialogHeader>
                <DialogTitle>修改文件分组</DialogTitle>
              </DialogHeader>
              <div className='space-y-2'>
                <label className='text-sm'>目标分组</label>
                <Select value={moveFolderValue} onValueChange={setMoveFolderValue}>
                  <SelectTrigger className='w-full'>
                    <SelectValue placeholder='请选择目标分组' />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='ROOT'>未分组</SelectItem>
                    {folderOptions.map((item) => (
                      <SelectItem key={item.id} value={String(item.id)}>
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <DialogFooter>
                <Button variant='outline' onClick={() => setMoveOpen(false)}>
                  取消
                </Button>
                <Button onClick={() => void submitMoveFile()}>保存</Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Dialog
            open={deleteFolderOpen}
            onOpenChange={(next) => {
              setDeleteFolderOpen(next)
              if (!next) setActiveFolder(null)
            }}
          >
            <DialogContent className='sm:max-w-md'>
              <DialogHeader>
                <DialogTitle>删除分组并迁移文件</DialogTitle>
              </DialogHeader>
              <div className='space-y-2'>
                <p className='text-sm text-muted-foreground'>
                  分组「{activeFolder?.name}」下存在文件，请先选择文件迁移到的分组。
                </p>
                <label className='text-sm'>目标分组</label>
                <Select value={deleteFolderValue} onValueChange={setDeleteFolderValue}>
                  <SelectTrigger className='w-full'>
                    <SelectValue placeholder='请选择目标分组' />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value='ROOT'>不分类</SelectItem>
                    {deleteFolderOptions.map((item) => (
                      <SelectItem key={item.id} value={String(item.id)}>
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <DialogFooter>
                <Button variant='outline' onClick={() => setDeleteFolderOpen(false)}>
                  取消
                </Button>
                <Button variant='destructive' onClick={() => void submitDeleteFolderWithMove()}>
                  确认删除
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <ConfirmDialog
            open={deleteFileOpen}
            onOpenChange={(next) => {
              setDeleteFileOpen(next)
              if (!next) setDeleteTargetFile(null)
            }}
            title='删除文件'
            desc={`确认删除文件「${deleteTargetFile?.name ?? ''}」吗？该操作不可恢复。`}
            confirmText='确认删除'
            destructive
            handleConfirm={() => void submitDeleteFile()}
          />

          <PhotoSlider
            images={imageFiles.map((item) => ({ key: String(item.id), src: item.url }))}
            visible={previewVisible}
            index={previewIndex}
            onIndexChange={setPreviewIndex}
            onClose={() => setPreviewVisible(false)}
          />
        </>
      )}
    </Main>
  )
}

export default FilesPage
