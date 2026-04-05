import { type ReactNode, useEffect, useMemo, useRef, useState } from 'react'
import { Check, FileImage, Film, FolderOpen, PanelLeft, Plus, Upload } from 'lucide-react'
import { PhotoSlider } from 'react-photo-view'
import { fileApi } from '@/api/file'
import type { FileFolder, FileKind, FileSource, StoredFile } from '@/api/file/types'
import { formatFileSize, getFileIcon, matchFileAccept } from '@/lib/file-utils'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useIsMobile } from '@/hooks/use-mobile'

type PickerVariant = 'image' | 'video' | 'file'

type FilePickerProps = {
  value?: StoredFile[]
  onChange?: (files: StoredFile[]) => void
  multiple?: boolean
  maxCount?: number
  accept?: string[]
  source?: FileSource | ''
  variant?: PickerVariant
  placeholder?: string
  className?: string
  render?: (api: {
    selectedFiles: StoredFile[]
    openPicker: () => void
    removeFile: (id: number) => void
    clearFiles: () => void
    previewFile: (file: StoredFile) => void
  }) => ReactNode
}

const listPageSize = 24

export function FilePicker({
  value = [],
  onChange,
  multiple = false,
  maxCount,
  accept = [],
  source = '',
  variant = 'file',
  placeholder = '请选择文件',
  className = '',
  render,
}: FilePickerProps) {
  const isMobile = useIsMobile()
  const inputRef = useRef<HTMLInputElement | null>(null)
  const [open, setOpen] = useState(false)
  const [folders, setFolders] = useState<FileFolder[]>([])
  const [folderId, setFolderId] = useState<number | null>(null)
  const [keyword, setKeyword] = useState('')
  const [searchKeyword, setSearchKeyword] = useState('')
  const [kind, setKind] = useState<FileKind | ''>(variant === 'image' ? 'IMAGE' : variant === 'video' ? 'VIDEO' : '')
  const [sourceFilter, setSourceFilter] = useState<FileSource | ''>(source)
  const [files, setFiles] = useState<StoredFile[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [innerSelected, setInnerSelected] = useState<Set<number>>(new Set())
  const [previewVisible, setPreviewVisible] = useState(false)
  const [previewIndex, setPreviewIndex] = useState(0)
  const [previewImages, setPreviewImages] = useState<StoredFile[]>([])
  const [folderPanelOpen, setFolderPanelOpen] = useState(false)
  const fixedKind = useMemo<FileKind | ''>(() => {
    if (variant === 'image') return 'IMAGE'
    if (variant === 'video') return 'VIDEO'
    return ''
  }, [variant])

  async function loadFolders() {
    const list = await fileApi.listFolders()
    setFolders(list)
  }

  async function loadFiles(targetPage = page) {
    setLoading(true)
    try {
      const result = await fileApi.listFiles({
        page: targetPage,
        pageSize: listPageSize,
        folderId,
        keyword: searchKeyword || undefined,
        source: sourceFilter,
        kind,
      })
      const filtered = result.list.filter((file) => matchFileAccept(file, accept))
      setFiles(filtered)
      setTotal(result.total)
      setPage(result.page)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    setKind(fixedKind)
  }, [fixedKind])

  useEffect(() => {
    if (!open) return
    void loadFolders()
    void loadFiles(1)
  }, [open, folderId, searchKeyword, sourceFilter, kind])

  useEffect(() => {
    if (!open) return
    setInnerSelected(new Set(value.map((file) => file.id)))
  }, [open, value])

  function toggleSelected(fileId: number) {
    setInnerSelected((prev) => {
      const next = new Set(prev)
      if (multiple) {
        if (next.has(fileId)) next.delete(fileId)
        else {
          if (maxCount && next.size >= maxCount) return next
          next.add(fileId)
        }
      } else {
        next.clear()
        next.add(fileId)
      }
      return next
    })
  }

  async function handleUpload(filesToUpload: FileList | null) {
    if (!filesToUpload?.length) return
    const uploaded = await fileApi.uploadFiles({
      files: Array.from(filesToUpload),
      folderId,
      source: sourceFilter || 'ADMIN',
    })
    const candidate = uploaded.filter((file) => matchFileAccept(file, accept))
    if (candidate.length > 0) {
      if (multiple) {
        const merged = [...value]
        candidate.forEach((item) => {
          if (!merged.find((it) => it.id === item.id)) merged.push(item)
        })
        onChange?.(maxCount ? merged.slice(0, maxCount) : merged)
      } else {
        onChange?.([candidate[0]])
      }
    }
    await loadFiles(1)
  }

  function handleConfirm() {
    const selected = files.filter((file) => innerSelected.has(file.id))
    if (multiple) {
      const merged = [
        ...value.filter((file) => !files.some((item) => item.id === file.id)),
        ...selected,
      ]
      onChange?.(maxCount ? merged.slice(0, maxCount) : merged)
    } else {
      onChange?.(selected.slice(0, 1))
    }
    setOpen(false)
  }

  function removeOne(id: number) {
    onChange?.(value.filter((item) => item.id !== id))
  }

  const title = variant === 'image' ? '选择图片' : variant === 'video' ? '选择视频' : '选择文件'

  function openImageGallery(file: StoredFile, gallery: StoredFile[]) {
    const images = gallery.filter((item) => item.kind === 'IMAGE')
    if (images.length === 0) return
    const index = Math.max(
      images.findIndex((item) => item.id === file.id),
      0
    )
    setPreviewImages(images)
    setPreviewIndex(index)
    setPreviewVisible(true)
  }

  function openVideo(file: StoredFile) {
    window.open(file.url, '_blank', 'noopener,noreferrer')
  }

  function previewFile(file: StoredFile) {
    if (file.kind === 'IMAGE') {
      openImageGallery(file, value)
      return
    }
    if (file.kind === 'VIDEO') {
      openVideo(file)
    }
  }

  function renderSelectedPreview(file: StoredFile, gallery: StoredFile[]) {
    if (file.kind === 'IMAGE') {
      return (
        <button
          type='button'
          className='h-10 w-10 overflow-hidden rounded'
          onClick={() => openImageGallery(file, gallery)}
        >
          <img src={file.url} alt={file.name} className='h-full w-full object-cover' />
        </button>
      )
    }

    if (file.kind === 'VIDEO') {
      return (
        <button
          type='button'
          className='inline-flex h-10 w-10 items-center justify-center rounded border bg-muted/20'
          onClick={() => openVideo(file)}
          title='打开视频'
        >
          <Film className='h-5 w-5 text-muted-foreground' />
        </button>
      )
    }

    const FileIcon = getFileIcon(file)
    return (
      <span className='inline-flex h-10 w-10 items-center justify-center rounded border bg-muted/20'>
        <FileIcon className='h-5 w-5 text-muted-foreground' />
      </span>
    )
  }

  function renderFolderPanel() {
    return (
      <>
        <Button
          type='button'
          variant={folderId === null ? 'secondary' : 'ghost'}
          className='mb-2 h-8 w-full justify-start'
          onClick={() => {
            setFolderId(null)
            if (isMobile) setFolderPanelOpen(false)
          }}
        >
          <FolderOpen className='mr-2 h-4 w-4' />
          全部
        </Button>
        <ScrollArea className='h-[50vh] pr-2 md:h-full'>
          {folders.map((folder) => (
            <Button
              key={folder.id}
              type='button'
              variant={folderId === folder.id ? 'secondary' : 'ghost'}
              className='mb-1 h-8 w-full justify-start'
              onClick={() => {
                setFolderId(folder.id)
                if (isMobile) setFolderPanelOpen(false)
              }}
            >
              <FolderOpen className='mr-2 h-4 w-4' />
              <span className='truncate'>{folder.name}</span>
            </Button>
          ))}
        </ScrollArea>
      </>
    )
  }

  return (
    <>
      <div className={className}>
        {render ? (
          render({
            selectedFiles: value,
            openPicker: () => setOpen(true),
            removeFile: removeOne,
            clearFiles: () => onChange?.([]),
            previewFile,
          })
        ) : variant === 'image' ? (
          <div className='flex flex-wrap items-start gap-2'>
            {value.map((file) => (
              <div
                key={file.id}
                className='relative h-24 w-24 shrink-0 overflow-hidden rounded-md border'
                role='button'
                tabIndex={0}
                onClick={() => openImageGallery(file, value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault()
                    openImageGallery(file, value)
                  }
                }}
                title='点击预览'
              >
                <img src={file.url} alt={file.name} className='h-full w-full object-cover' />
                <button
                  type='button'
                  className='absolute right-1 top-1 rounded bg-black/50 px-1 text-xs text-white'
                  onClick={(e) => {
                    e.stopPropagation()
                    removeOne(file.id)
                  }}
                >
                  ×
                </button>
              </div>
            ))}
            {(!maxCount || value.length < maxCount) && (
              <button
                type='button'
                className='flex h-24 w-24 shrink-0 flex-col items-center justify-center gap-1 rounded-md border border-dashed text-sm text-muted-foreground hover:bg-muted/50'
                onClick={() => setOpen(true)}
              >
                <Plus className='h-5 w-5' />
                <span className='leading-none'>选择图片</span>
              </button>
            )}
          </div>
        ) : (
          <div className='space-y-2'>
            <Button
              type='button'
              variant='outline'
              className='w-full justify-start'
              onClick={() => setOpen(true)}
            >
              {variant === 'video' ? <Film className='mr-2 h-4 w-4' /> : <FileImage className='mr-2 h-4 w-4' />}
              {value.length > 0 ? `已选择 ${value.length} 个文件` : placeholder}
            </Button>
            {value.length > 0 && (
              <div className='space-y-1 rounded-md border p-2'>
                {value.map((file) => {
                  return (
                    <div key={file.id} className='flex items-center justify-between gap-2 text-sm'>
                      <div className='flex min-w-0 items-center gap-2'>
                        {renderSelectedPreview(file, value)}
                        <span className='truncate'>{file.name}</span>
                      </div>
                      <button
                        type='button'
                        className='text-xs text-muted-foreground hover:text-destructive'
                        onClick={() => removeOne(file.id)}
                      >
                        删除
                      </button>
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        )}
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className='w-[calc(100vw-1rem)] gap-0 max-w-[calc(100vw-1rem)] overflow-hidden p-0 sm:w-[calc(100vw-2rem)] sm:max-w-[calc(100vw-2rem)] lg:w-[1200px] lg:max-w-[1200px]'>
          <DialogHeader className='border-b px-5 py-4'>
            <DialogTitle>{title}</DialogTitle>
          </DialogHeader>
          <div className='flex h-[min(90vh,820px)] flex-col overflow-hidden'>
            <div className='grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[240px_minmax(0,1fr)]'>
              <div className='hidden md:block md:border-r'>
                <div className='h-full p-3'>{renderFolderPanel()}</div>
              </div>

              <div className='flex min-h-0 min-w-0 flex-col p-4'>
                <div className='mb-3 flex flex-wrap items-center gap-2'>
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
                    ref={inputRef}
                    type='file'
                    className='hidden'
                    multiple={multiple}
                    accept={accept.join(',')}
                    onChange={(e) => void handleUpload(e.target.files)}
                  />
                  <Button type='button' onClick={() => inputRef.current?.click()}>
                    <Upload className='mr-1 h-4 w-4' />
                    本地上传
                  </Button>
                  <Select
                    value={sourceFilter || 'ALL'}
                    onValueChange={(v) => setSourceFilter(v === 'ALL' ? '' : (v as FileSource))}
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
                    disabled={variant === 'image' || variant === 'video'}
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
                  <div className='ml-auto flex w-full items-center gap-2 md:w-auto'>
                    <Input
                      placeholder='请输入名称'
                      value={keyword}
                      onChange={(e) => setKeyword(e.target.value)}
                      className='w-full md:w-[240px]'
                    />
                    <Button type='button' variant='outline' onClick={() => setSearchKeyword(keyword)}>
                      搜索
                    </Button>
                  </div>
                </div>

                <div className='min-h-0 flex-1 overflow-y-auto'>
                  <div className='grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5'>
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
                        const checked = innerSelected.has(file.id)
                        const FileIcon = getFileIcon(file)
                        return (
                          <button
                            key={file.id}
                            type='button'
                            className={`rounded-md border p-2 text-left transition-colors ${
                              checked ? 'border-primary bg-primary/5' : ''
                            }`}
                            onClick={() => toggleSelected(file.id)}
                          >
                            <div className='relative h-24 overflow-hidden rounded-md border bg-muted/20'>
                              {file.kind === 'IMAGE' ? (
                                <img src={file.url} alt={file.name} className='h-full w-full object-cover' />
                              ) : (
                                <div className='flex h-full w-full items-center justify-center'>
                                  <FileIcon className='h-8 w-8 text-muted-foreground' />
                                </div>
                              )}
                              {checked && (
                                <span className='absolute right-1 top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground'>
                                  <Check className='h-3 w-3' />
                                </span>
                              )}
                            </div>
                            <div className='mt-1 truncate text-sm' title={file.name}>
                              {file.name}
                            </div>
                            <div className='text-xs text-muted-foreground'>{formatFileSize(file.size)}</div>
                          </button>
                        )
                      })
                    )}
                  </div>
                </div>

                <div className='mt-3 flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground'>
                  <span>
                    共 {total} 条，已选 {innerSelected.size}
                    {multiple && maxCount ? ` / ${maxCount}` : ''}
                  </span>
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
                      {page}/{Math.max(Math.ceil(total / listPageSize), 1)}
                    </span>
                    <Button
                      variant='outline'
                      size='sm'
                      disabled={page >= Math.max(Math.ceil(total / listPageSize), 1)}
                      onClick={() => void loadFiles(page + 1)}
                    >
                      下一页
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <DialogFooter className='shrink-0 border-t px-5 py-3 sm:justify-end'>
              <Button variant='outline' onClick={() => setOpen(false)}>
                取消
              </Button>
              <Button onClick={handleConfirm}>确定</Button>
            </DialogFooter>
          </div>
        </DialogContent>
      </Dialog>
      <Sheet open={folderPanelOpen} onOpenChange={setFolderPanelOpen}>
        <SheetContent side='left' className='w-[320px] p-0 sm:w-[360px]'>
          <SheetHeader className='border-b px-4 py-3'>
            <SheetTitle>文件分组</SheetTitle>
          </SheetHeader>
          <div className='p-3'>{renderFolderPanel()}</div>
        </SheetContent>
      </Sheet>
      <PhotoSlider
        images={previewImages.map((item) => ({ key: String(item.id), src: item.url }))}
        visible={previewVisible}
        index={previewIndex}
        onIndexChange={setPreviewIndex}
        onClose={() => setPreviewVisible(false)}
      />
    </>
  )
}
