import { useMemo, useState } from 'react'
import { format } from 'date-fns'
import { zhCN } from 'date-fns/locale'
import { Calendar as CalendarIcon, Check, Clock3, X } from 'lucide-react'
import type { DateRange } from 'react-day-picker'
import { DatePicker } from '@/components/date-picker'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

function pad2(value: number) {
  return String(value).padStart(2, '0')
}

function timeText(date: Date | null) {
  if (!date) return '请选择时间'
  return format(date, 'HH:mm:ss')
}

function dateTimeText(date: Date | null) {
  if (!date) return '请选择日期时间'
  return format(date, 'yyyy-MM-dd HH:mm:ss')
}

function rangeText(range: DateRange | undefined) {
  const start = range?.from
  const end = range?.to
  if (!start && !end) return '请选择日期范围'
  if (start && end) return `${format(start, 'yyyy-MM-dd')} ~ ${format(end, 'yyyy-MM-dd')}`
  if (start) return `${format(start, 'yyyy-MM-dd')} ~`
  return `~ ${format(end as Date, 'yyyy-MM-dd')}`
}

function setTimePart(base: Date, h: number, m: number, s: number) {
  const next = new Date(base)
  next.setHours(h)
  next.setMinutes(m)
  next.setSeconds(s)
  next.setMilliseconds(0)
  return next
}

function toYmd(date: Date | undefined) {
  return date ? format(date, 'yyyy-MM-dd') : null
}

function toHms(date: Date | null) {
  return date ? format(date, 'HH:mm:ss') : null
}

function toYmdHms(date: Date | null) {
  return date ? format(date, 'yyyy-MM-dd HH:mm:ss') : null
}

function toRange(range: DateRange | undefined) {
  if (!range?.from && !range?.to) return null
  return [toYmd(range?.from), toYmd(range?.to)]
}

function toDateTimeRange(range: { from: Date | null; to: Date | null }) {
  if (!range.from && !range.to) return null
  return [toYmdHms(range.from), toYmdHms(range.to)]
}

function TimeSelector({
  value,
  onChange,
}: {
  value: Date
  onChange: (next: Date) => void
}) {
  const hour = value.getHours()
  const minute = value.getMinutes()
  const second = value.getSeconds()

  return (
    <div className='grid grid-cols-3 gap-2'>
      <Select value={pad2(hour)} onValueChange={(v) => onChange(setTimePart(value, Number(v), minute, second))}>
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

      <Select value={pad2(minute)} onValueChange={(v) => onChange(setTimePart(value, hour, Number(v), second))}>
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

      <Select value={pad2(second)} onValueChange={(v) => onChange(setTimePart(value, hour, minute, Number(v)))}>
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

export default function DateTimePickerDemoPage() {
  const [date, setDate] = useState<Date | undefined>()
  const [timeOpen, setTimeOpen] = useState(false)
  const [time, setTime] = useState<Date | null>(null)

  const [dateTimeOpen, setDateTimeOpen] = useState(false)
  const [dateTime, setDateTime] = useState<Date | null>(null)

  const [dateRangeOpen, setDateRangeOpen] = useState(false)
  const [dateRange, setDateRange] = useState<DateRange | undefined>()

  const [dateTimeRangeOpen, setDateTimeRangeOpen] = useState(false)
  const [dateTimeRange, setDateTimeRange] = useState<{ from: Date | null; to: Date | null }>({
    from: null,
    to: null,
  })

  const fromTime = useMemo(() => dateTimeRange.from ?? new Date(), [dateTimeRange.from])
  const toTime = useMemo(() => dateTimeRange.to ?? new Date(), [dateTimeRange.to])

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>日期时间选择器（模板组件示例）</h1>
        <p className='text-muted-foreground'>基于模板内置的 DatePicker、Calendar、Popover、Select 组合实现。</p>
      </div>

      <div className='grid gap-4 xl:grid-cols-2'>
        <Card className='shadow-none'>
          <CardHeader>
            <CardTitle className='text-base'>日期选择</CardTitle>
            <CardDescription>格式：yyyy-MM-dd</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            <Label>日期选择</Label>
            <DatePicker selected={date} onSelect={setDate} placeholder='请选择日期' />
            <pre className='rounded-md border bg-muted/30 p-3 text-xs'>{JSON.stringify(toYmd(date), null, 2)}</pre>
          </CardContent>
        </Card>

        <Card className='shadow-none'>
          <CardHeader>
            <CardTitle className='text-base'>时间选择</CardTitle>
            <CardDescription>格式：HH:mm:ss</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            <Label>时间选择</Label>
            <Popover open={timeOpen} onOpenChange={setTimeOpen}>
              <PopoverTrigger asChild>
                <Button variant='outline' className='w-full justify-start'>
                  <Clock3 className='mr-2 h-4 w-4' />
                  {timeText(time)}
                </Button>
              </PopoverTrigger>
              <PopoverContent className='w-[360px] p-3' align='start'>
                <div className='space-y-3'>
                  <TimeSelector value={time ?? new Date()} onChange={setTime} />
                  <div className='flex justify-end gap-2 border-t pt-2'>
                    <Button size='sm' variant='outline' onClick={() => setTime(null)}>
                      <X className='mr-1 h-4 w-4' />清空
                    </Button>
                    <Button size='sm' onClick={() => setTimeOpen(false)}>
                      <Check className='mr-1 h-4 w-4' />确定
                    </Button>
                  </div>
                </div>
              </PopoverContent>
            </Popover>
            <pre className='rounded-md border bg-muted/30 p-3 text-xs'>{JSON.stringify(toHms(time), null, 2)}</pre>
          </CardContent>
        </Card>

        <Card className='shadow-none'>
          <CardHeader>
            <CardTitle className='text-base'>日期时间选择</CardTitle>
            <CardDescription>格式：yyyy-MM-dd HH:mm:ss</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            <Label>日期时间选择</Label>
            <Popover open={dateTimeOpen} onOpenChange={setDateTimeOpen}>
              <PopoverTrigger asChild>
                <Button variant='outline' className='w-full justify-start'>
                  <CalendarIcon className='mr-2 h-4 w-4' />
                  {dateTimeText(dateTime)}
                </Button>
              </PopoverTrigger>
              <PopoverContent className='w-fit p-3' align='start'>
                <div className='space-y-3'>
                  <Calendar
                    locale={zhCN}
                    mode='single'
                    selected={dateTime ?? undefined}
                    onSelect={(selected) => {
                      if (!selected) return
                      if (!dateTime) {
                        setDateTime(selected)
                        return
                      }
                      setDateTime(setTimePart(selected, dateTime.getHours(), dateTime.getMinutes(), dateTime.getSeconds()))
                    }}
                  />
                  <TimeSelector value={dateTime ?? new Date()} onChange={setDateTime} />
                  <div className='flex justify-end gap-2 border-t pt-2'>
                    <Button size='sm' variant='outline' onClick={() => setDateTime(null)}>
                      <X className='mr-1 h-4 w-4' />清空
                    </Button>
                    <Button size='sm' onClick={() => setDateTimeOpen(false)}>
                      <Check className='mr-1 h-4 w-4' />确定
                    </Button>
                  </div>
                </div>
              </PopoverContent>
            </Popover>
            <pre className='rounded-md border bg-muted/30 p-3 text-xs'>{JSON.stringify(toYmdHms(dateTime), null, 2)}</pre>
          </CardContent>
        </Card>

        <Card className='shadow-none'>
          <CardHeader>
            <CardTitle className='text-base'>日期范围选择</CardTitle>
            <CardDescription>格式：yyyy-MM-dd</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            <Label>日期范围选择</Label>
            <Popover open={dateRangeOpen} onOpenChange={setDateRangeOpen}>
              <PopoverTrigger asChild>
                <Button variant='outline' className='w-full justify-start'>
                  <CalendarIcon className='mr-2 h-4 w-4' />
                  {rangeText(dateRange)}
                </Button>
              </PopoverTrigger>
              <PopoverContent
                className='w-[min(calc(100vw-2rem),760px)] p-3'
                align='start'
                sideOffset={6}
                collisionPadding={16}
              >
                <Calendar
                  locale={zhCN}
                  mode='range'
                  selected={dateRange}
                  onSelect={setDateRange}
                  numberOfMonths={2}
                  className='w-full p-0'
                  classNames={{
                    root: 'w-full',
                    months: 'grid w-full grid-cols-1 gap-4 md:grid-cols-2',
                  }}
                />
              </PopoverContent>
            </Popover>
            <pre className='rounded-md border bg-muted/30 p-3 text-xs'>{JSON.stringify(toRange(dateRange), null, 2)}</pre>
          </CardContent>
        </Card>

        <Card className='shadow-none xl:col-span-2'>
          <CardHeader>
            <CardTitle className='text-base'>日期时间范围选择</CardTitle>
            <CardDescription>格式：yyyy-MM-dd HH:mm:ss</CardDescription>
          </CardHeader>
          <CardContent className='space-y-3'>
            <Label>日期时间范围选择</Label>
            <Popover open={dateTimeRangeOpen} onOpenChange={setDateTimeRangeOpen}>
              <PopoverTrigger asChild>
                <Button variant='outline' className='w-full justify-start'>
                  <CalendarIcon className='mr-2 h-4 w-4' />
                  {dateTimeRange.from || dateTimeRange.to
                    ? `${dateTimeRange.from ? format(dateTimeRange.from, 'yyyy-MM-dd HH:mm:ss') : ''} ~ ${dateTimeRange.to ? format(dateTimeRange.to, 'yyyy-MM-dd HH:mm:ss') : ''}`
                    : '请选择日期时间范围'}
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
                    numberOfMonths={2}
                    className='w-full p-0'
                    classNames={{
                      root: 'w-full',
                      months: 'grid w-full grid-cols-1 gap-4 md:grid-cols-2',
                    }}
                    selected={{ from: dateTimeRange.from ?? undefined, to: dateTimeRange.to ?? undefined }}
                    onSelect={(next) => {
                      setDateTimeRange((prev) => {
                        const from = next?.from
                          ? setTimePart(next.from, prev.from?.getHours() ?? 0, prev.from?.getMinutes() ?? 0, prev.from?.getSeconds() ?? 0)
                          : null
                        const to = next?.to
                          ? setTimePart(next.to, prev.to?.getHours() ?? 0, prev.to?.getMinutes() ?? 0, prev.to?.getSeconds() ?? 0)
                          : null
                        return { from, to }
                      })
                    }}
                  />

                  <div className='grid gap-3 md:grid-cols-2'>
                    <div className='space-y-2'>
                      <Label className='text-xs text-muted-foreground'>开始时间</Label>
                      <TimeSelector
                        value={fromTime}
                        onChange={(next) => setDateTimeRange((prev) => ({ ...prev, from: next }))}
                      />
                    </div>
                    <div className='space-y-2'>
                      <Label className='text-xs text-muted-foreground'>结束时间</Label>
                      <TimeSelector
                        value={toTime}
                        onChange={(next) => setDateTimeRange((prev) => ({ ...prev, to: next }))}
                      />
                    </div>
                  </div>

                  <div className='flex justify-end gap-2 border-t pt-2'>
                    <Button
                      size='sm'
                      variant='outline'
                      onClick={() => setDateTimeRange({ from: null, to: null })}
                    >
                      <X className='mr-1 h-4 w-4' />清空
                    </Button>
                    <Button size='sm' onClick={() => setDateTimeRangeOpen(false)}>
                      <Check className='mr-1 h-4 w-4' />确定
                    </Button>
                  </div>
                </div>
              </PopoverContent>
            </Popover>
            <pre className='rounded-md border bg-muted/30 p-3 text-xs'>
{JSON.stringify(toDateTimeRange(dateTimeRange), null, 2)}
            </pre>
          </CardContent>
        </Card>
      </div>
    </Main>
  )
}
