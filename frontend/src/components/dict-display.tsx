import { useMemo } from 'react'
import { Badge } from '@/components/ui/badge'
import { useDictOptions } from '@/hooks/use-dict-options'
import { cn } from '@/lib/utils'

const tagTypeClassMap: Record<string, string> = {
  success: 'border-green-200 bg-green-50 text-green-700 hover:bg-green-50',
  warning: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-50',
  danger: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-50',
  info: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-50',
  neutral: 'border-zinc-200 bg-zinc-100 text-zinc-700 hover:bg-zinc-100',
}

type DictDisplayProps = {
  dictCode: string
  value?: string | number | boolean | null
  mode?: 'text' | 'badge'
  fallback?: string
  className?: string
  valueClassMap?: Record<string, string>
}

function normalizeValue(value: DictDisplayProps['value']) {
  if (value === null || value === undefined) return ''
  if (typeof value === 'boolean') return value ? '1' : '0'
  return String(value)
}

export function DictDisplay({
  dictCode,
  value,
  mode = 'text',
  fallback = '-',
  className,
  valueClassMap,
}: DictDisplayProps) {
  const normalizedValue = normalizeValue(value)
  const { data: options = [] } = useDictOptions(dictCode, !!dictCode)

  const option = useMemo(
    () => options.find((it) => it.value === normalizedValue),
    [options, normalizedValue]
  )

  if (!normalizedValue) return <>{fallback}</>

  const label = option?.label || normalizedValue
  if (mode === 'text') return <span className={className}>{label}</span>

  const classFromValue = valueClassMap?.[normalizedValue]
  const classFromType = option?.tagType ? tagTypeClassMap[option.tagType] : ''
  const badgeClass = option?.tagClass || classFromValue || classFromType || 'border-zinc-200 bg-zinc-100 text-zinc-700 hover:bg-zinc-100'

  return <Badge className={cn(badgeClass, className)}>{label}</Badge>
}
