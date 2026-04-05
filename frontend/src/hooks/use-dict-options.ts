import { useQuery } from '@tanstack/react-query'
import { dictionaryApi } from '@/api/dictionary'

export function useDictOptions(code: string, enabled = true) {
  return useQuery({
    queryKey: ['dict-options', code],
    queryFn: () => dictionaryApi.optionsByCode(code),
    enabled: enabled && !!code,
    staleTime: 5 * 60 * 1000,
  })
}
