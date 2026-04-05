import { useEffect, useMemo, useState, type ReactNode } from 'react'
import dynamicIconImports from 'lucide-react/dynamicIconImports'
import { Check, ChevronDown, ChevronLeft, ChevronRight, Circle } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

export type IconPickerOption = {
  value: string
  label?: string
}

type DynamicIconModule = { default: LucideIcon }
type DynamicIconLoader = () => Promise<DynamicIconModule>

const iconImportMap = dynamicIconImports as Record<string, DynamicIconLoader>
const iconNameList = Object.keys(iconImportMap).sort((a, b) => a.localeCompare(b))

const ALL_ICON_OPTIONS: IconPickerOption[] = iconNameList.map((name) => ({
  value: name,
  label: name,
}))

const iconCache = new Map<string, LucideIcon | null>()

function toKebabCase(name: string) {
  return name
    .replace(/Icon$/, '')
    .replace(/_/g, '-')
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .toLowerCase()
}

export function resolveLucideIconKey(name?: string | null) {
  if (!name) return null
  const raw = name.trim()
  if (!raw) return null

  if (iconImportMap[raw]) return raw

  const normalized = toKebabCase(raw)
  if (iconImportMap[normalized]) return normalized

  return null
}

export async function loadLucideIconByName(name?: string | null) {
  const key = resolveLucideIconKey(name)
  if (!key) return null

  if (iconCache.has(key)) {
    return iconCache.get(key) ?? null
  }

  const loader = iconImportMap[key]
  if (!loader) {
    iconCache.set(key, null)
    return null
  }

  try {
    const mod = await loader()
    const icon = mod.default ?? null
    iconCache.set(key, icon)
    return icon
  } catch {
    iconCache.set(key, null)
    return null
  }
}

type LucideIconByNameProps = {
  name?: string | null
  className?: string
  fallback?: ReactNode
}

export function LucideIconByName({
  name,
  className,
  fallback = <Circle className={className} />,
}: LucideIconByNameProps) {
  const [Icon, setIcon] = useState<LucideIcon | null>(null)

  useEffect(() => {
    let disposed = false

    void loadLucideIconByName(name).then((loaded) => {
      if (!disposed) setIcon(() => loaded)
    })

    return () => {
      disposed = true
    }
  }, [name])

  if (!name) return fallback
  if (!Icon) return fallback
  return <Icon className={className} />
}

type IconPickerProps = {
  value?: string
  onValueChange: (value: string) => void
  options?: IconPickerOption[]
  placeholder?: string
  className?: string
  pageSize?: number
  disabled?: boolean
}

export function IconPicker({
  value = '',
  onValueChange,
  options,
  placeholder = '请选择图标',
  className,
  pageSize = 48,
  disabled = false,
}: IconPickerProps) {
  const [open, setOpen] = useState(false)
  const [keyword, setKeyword] = useState('')
  const [page, setPage] = useState(1)
  const [pageSizeValue, setPageSizeValue] = useState(pageSize)
  const [isMobile, setIsMobile] = useState(false)
  const collisionBoundary = typeof document === 'undefined' ? undefined : document.body

  const mergedOptions = useMemo(() => options ?? ALL_ICON_OPTIONS, [options])

  const selected = useMemo(() => {
    if (!value) return null
    const direct = mergedOptions.find((item) => item.value === value)
    if (direct) return direct

    const resolved = resolveLucideIconKey(value)
    if (!resolved) return null

    return mergedOptions.find((item) => item.value === resolved) ?? {
      value: resolved,
      label: resolved,
    }
  }, [mergedOptions, value])

  const filteredOptions = useMemo(() => {
    const key = keyword.trim().toLowerCase()
    if (!key) return mergedOptions
    return mergedOptions.filter((item) => {
      const text = `${item.value} ${item.label ?? ''}`.toLowerCase()
      return text.includes(key)
    })
  }, [keyword, mergedOptions])

  const totalPages = Math.max(1, Math.ceil(filteredOptions.length / pageSizeValue))
  const currentPage = Math.min(page, totalPages)

  const pagedOptions = useMemo(() => {
    const start = (currentPage - 1) * pageSizeValue
    return filteredOptions.slice(start, start + pageSizeValue)
  }, [currentPage, filteredOptions, pageSizeValue])

  useEffect(() => {
    if (!open) return
    setKeyword('')
    setPage(1)
  }, [open])

  useEffect(() => {
    if (typeof window === 'undefined') return
    const media = window.matchMedia('(max-width: 640px)')
    const onChange = () => setIsMobile(media.matches)
    onChange()
    media.addEventListener('change', onChange)
    return () => media.removeEventListener('change', onChange)
  }, [])

  return (
    <Popover
      open={open}
      onOpenChange={(next) => {
        if (disabled) return
        setOpen(next)
      }}
    >
      <PopoverTrigger asChild>
        <Button
          type='button'
          variant='outline'
          disabled={disabled}
          className={cn('h-9 w-full justify-between', className)}
        >
          {selected ? (
            <span className='flex min-w-0 items-center gap-2'>
              <LucideIconByName name={selected.value} className='h-4 w-4 shrink-0' />
              <span className='truncate'>{selected.value}</span>
            </span>
          ) : (
            <span className='text-muted-foreground'>{placeholder}</span>
          )}
          <ChevronDown className='h-4 w-4 opacity-60' />
        </Button>
      </PopoverTrigger>

      <PopoverContent
        side='bottom'
        align={isMobile ? 'center' : 'start'}
        collisionPadding={8}
        collisionBoundary={collisionBoundary}
        sideOffset={6}
        sticky='always'
        className='w-[calc(100vw-1rem)] max-w-[560px] overflow-hidden p-3'
        style={{
          maxWidth: 'min(560px, calc(var(--radix-popover-content-available-width) - 8px))',
          maxHeight: 'calc(var(--radix-popover-content-available-height) - 8px)',
        }}
      >
        <div className='flex min-h-0 flex-col gap-3 overflow-hidden'>
          <div className='grid grid-cols-1 gap-2 sm:grid-cols-[1fr_120px]'>
            <Input
              value={keyword}
              onChange={(e) => {
                setKeyword(e.target.value)
                setPage(1)
              }}
              placeholder='搜索图标（支持关键字）'
            />
            <Select
              value={String(pageSizeValue)}
              onValueChange={(next) => {
                setPageSizeValue(Number(next))
                setPage(1)
              }}
            >
              <SelectTrigger className='h-9'>
                <SelectValue placeholder='每页数量' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='48'>每页 48</SelectItem>
                <SelectItem value='72'>每页 72</SelectItem>
                <SelectItem value='120'>每页 120</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div
            className='h-[min(46dvh,420px)] min-h-[220px] overflow-y-auto overscroll-contain rounded-md border p-2 pr-3 [scrollbar-gutter:stable] [-webkit-overflow-scrolling:touch]'
            style={{
              maxHeight:
                'min(420px, calc(var(--radix-popover-content-available-height) - 200px))',
            }}
            onWheelCapture={(e) => e.stopPropagation()}
            onTouchMove={(e) => e.stopPropagation()}
          >
            <div className='grid grid-cols-2 gap-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6'>
              <button
                type='button'
                className='flex h-16 flex-col items-center justify-center rounded border border-dashed p-2 text-xs hover:bg-muted'
                onClick={() => {
                  onValueChange('')
                  setOpen(false)
                }}
              >
                <span className='text-muted-foreground'>无图标</span>
                {!value ? <Check className='mt-1 h-4 w-4' /> : null}
              </button>

              {pagedOptions.map((item) => (
                <button
                  key={item.value}
                  type='button'
                  className={cn(
                    'relative flex h-16 flex-col items-center justify-center rounded border p-2 text-xs hover:bg-muted',
                    value === item.value && 'border-primary bg-muted'
                  )}
                  onClick={() => {
                    onValueChange(item.value)
                    setOpen(false)
                  }}
                  title={item.value}
                >
                  <LucideIconByName name={item.value} className='h-4 w-4' />
                  <span className='mt-1 max-w-full truncate'>{item.value}</span>
                  {value === item.value ? (
                    <Check className='absolute right-1 top-1 h-3.5 w-3.5 text-primary' />
                  ) : null}
                </button>
              ))}
            </div>

            {pagedOptions.length === 0 ? (
              <div className='px-2 py-8 text-center text-sm text-muted-foreground'>
                未找到图标
              </div>
            ) : null}
          </div>

          <div className='flex flex-wrap items-center justify-between gap-2 border-t pt-2 text-xs text-muted-foreground'>
            <span>
              共 {filteredOptions.length} 个图标，第 {currentPage}/{totalPages} 页
            </span>
            <div className='flex items-center gap-1'>
              <Button
                type='button'
                variant='outline'
                size='icon'
                className='h-7 w-7'
                disabled={currentPage <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                <ChevronLeft className='h-4 w-4' />
              </Button>
              <Button
                type='button'
                variant='outline'
                size='icon'
                className='h-7 w-7'
                disabled={currentPage >= totalPages}
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              >
                <ChevronRight className='h-4 w-4' />
              </Button>
            </div>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  )
}
