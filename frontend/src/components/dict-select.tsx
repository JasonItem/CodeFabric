import { useMemo } from 'react'
import { useDictOptions } from '@/hooks/use-dict-options'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

type DictSelectProps = {
  dictCode: string
  value?: string | null
  onValueChange: (value: string) => void
  placeholder?: string
  allowAll?: boolean
  allValue?: string
  allLabel?: string
  disabled?: boolean
  className?: string
  triggerClassName?: string
}

export function DictSelect({
  dictCode,
  value,
  onValueChange,
  placeholder = '请选择',
  allowAll = false,
  allValue = 'all',
  allLabel = '全部',
  disabled = false,
  className,
  triggerClassName,
}: DictSelectProps) {
  const { data: options = [], isLoading } = useDictOptions(dictCode, !!dictCode)

  const normalized = useMemo(() => (value == null ? '' : String(value)), [value])

  return (
    <Select
      value={normalized}
      disabled={disabled || isLoading}
      onValueChange={onValueChange}
    >
      <SelectTrigger className={triggerClassName ?? 'w-full'}>
        <SelectValue placeholder={isLoading ? '加载中...' : placeholder} />
      </SelectTrigger>
      <SelectContent className={className}>
        {allowAll && <SelectItem value={allValue}>{allLabel}</SelectItem>}
        {options.map((item) => (
          <SelectItem key={item.id} value={item.value}>
            {item.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
