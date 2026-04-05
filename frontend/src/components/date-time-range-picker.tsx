import { useMemo, useState } from 'react'
import { format } from 'date-fns'
import { zhCN } from 'date-fns/locale'
import { Calendar as CalendarIcon, Check, X } from 'lucide-react'
import { Calendar } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useIsMobile } from '@/hooks/use-mobile'

type DateTimeRangePickerProps = {
  startTime?: string
  endTime?: string
  placeholder?: string
  className?: string
  onChange: (next: { startTime: string; endTime: string }) => void
}

function pad2(value: number) {
  return String(value).padStart(2, '0')
}

function parseDate(value?: string) {
  if (!value) return null
  const normalized = value.includes('T') ? value : value.replace(' ', 'T')
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? null : date
}

function withTime(base: Date, h: number, m: number, s: number) {
  const next = new Date(base)
  next.setHours(h, m, s, 0)
  return next
}

function formatDateTime(value: Date | null) {
  return value ? format(value, 'yyyy-MM-dd HH:mm:ss') : ''
}

function triggerText(from: Date | null, to: Date | null, placeholder: string) {
  if (!from && !to) return placeholder
  return `${from ? formatDateTime(from) : ''} ~ ${to ? formatDateTime(to) : ''}`
}

function TimeSelector({
  value,
  onChange,
  disabled,
}: {
  value: Date
  onChange: (next: Date) => void
  disabled?: boolean
}) {
  const hour = value.getHours()
  const minute = value.getMinutes()
  const second = value.getSeconds()

  return (
    <div className='grid grid-cols-3 gap-2'>
      <Select
        disabled={disabled}
        value={pad2(hour)}
        onValueChange={(v) => onChange(withTime(value, Number(v), minute, second))}
      >
        <SelectTrigger className='h-9'>
          <SelectValue placeholder='时' />
        </SelectTrigger>
        <SelectContent>
          {Array.from({ length: 24 }).map((_, i) => (
            <SelectItem key={i} value={pad2(i)}>
              {pad2(i)} 时
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Select
        disabled={disabled}
        value={pad2(minute)}
        onValueChange={(v) => onChange(withTime(value, hour, Number(v), second))}
      >
        <SelectTrigger className='h-9'>
          <SelectValue placeholder='分' />
        </SelectTrigger>
        <SelectContent>
          {Array.from({ length: 60 }).map((_, i) => (
            <SelectItem key={i} value={pad2(i)}>
              {pad2(i)} 分
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Select
        disabled={disabled}
        value={pad2(second)}
        onValueChange={(v) => onChange(withTime(value, hour, minute, Number(v)))}
      >
        <SelectTrigger className='h-9'>
          <SelectValue placeholder='秒' />
        </SelectTrigger>
        <SelectContent>
          {Array.from({ length: 60 }).map((_, i) => (
            <SelectItem key={i} value={pad2(i)}>
              {pad2(i)} 秒
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  )
}

export function DateTimeRangePicker({
  startTime,
  endTime,
  placeholder = '操作时间范围',
  className = '',
  onChange,
}: DateTimeRangePickerProps) {
  const isMobile = useIsMobile()
  const [open, setOpen] = useState(false)
  const [draftFrom, setDraftFrom] = useState<Date | null>(parseDate(startTime))
  const [draftTo, setDraftTo] = useState<Date | null>(parseDate(endTime))

  const fromTime = useMemo(() => draftFrom ?? new Date(), [draftFrom])
  const toTime = useMemo(() => draftTo ?? new Date(), [draftTo])

  function openPanel(nextOpen: boolean) {
    setOpen(nextOpen)
    if (nextOpen) {
      setDraftFrom(parseDate(startTime))
      setDraftTo(parseDate(endTime))
    }
  }

  function confirm() {
    onChange({
      startTime: formatDateTime(draftFrom),
      endTime: formatDateTime(draftTo),
    })
    setOpen(false)
  }

  function clear() {
    setDraftFrom(null)
    setDraftTo(null)
    onChange({ startTime: '', endTime: '' })
  }

  return (
    <Popover open={open} onOpenChange={openPanel}>
      <PopoverTrigger asChild>
        <Button variant='outline' className={`w-full justify-start ${className}`}>
          <CalendarIcon className='mr-2 h-4 w-4' />
          <span className='truncate'>{triggerText(parseDate(startTime), parseDate(endTime), placeholder)}</span>
        </Button>
      </PopoverTrigger>
      <PopoverContent
        className='w-[min(calc(100vw-2rem),760px)] p-3'
        align='start'
        sideOffset={6}
        collisionPadding={16}
      >
        <div className='space-y-3'>
          <Calendar
            locale={zhCN}
            mode='range'
            className='w-full p-0'
            classNames={{
              root: 'w-full',
              months: 'grid w-full grid-cols-1 gap-4 md:grid-cols-2',
            }}
            numberOfMonths={isMobile ? 1 : 2}
            selected={{ from: draftFrom ?? undefined, to: draftTo ?? undefined }}
            onSelect={(next) => {
              setDraftFrom((prevFrom) => {
                if (!next?.from) return null
                return withTime(
                  next.from,
                  prevFrom?.getHours() ?? 0,
                  prevFrom?.getMinutes() ?? 0,
                  prevFrom?.getSeconds() ?? 0
                )
              })
              setDraftTo((prevTo) => {
                if (!next?.to) return null
                return withTime(
                  next.to,
                  prevTo?.getHours() ?? 23,
                  prevTo?.getMinutes() ?? 59,
                  prevTo?.getSeconds() ?? 59
                )
              })
            }}
          />

          <div className='grid gap-3 md:grid-cols-2'>
            <div className='space-y-2'>
              <Label className='text-xs text-muted-foreground'>开始时间</Label>
              <TimeSelector
                value={fromTime}
                disabled={!draftFrom}
                onChange={(next) => setDraftFrom(next)}
              />
            </div>
            <div className='space-y-2'>
              <Label className='text-xs text-muted-foreground'>结束时间</Label>
              <TimeSelector
                value={toTime}
                disabled={!draftTo}
                onChange={(next) => setDraftTo(next)}
              />
            </div>
          </div>

          <div className='flex justify-end gap-2 border-t pt-2'>
            <Button size='sm' variant='outline' onClick={clear}>
              <X className='mr-1 h-4 w-4' />
              清空
            </Button>
            <Button size='sm' onClick={confirm}>
              <Check className='mr-1 h-4 w-4' />
              确定
            </Button>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  )
}
