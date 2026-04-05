import { httpClient } from '@/api/http'
import type {
  OperationLogDetail,
  OperationLogQuery,
  PagedOperationLog,
} from '@/api/operation-log/types'

export const operationLogApi = {
  list(query: OperationLogQuery) {
    return httpClient.get<PagedOperationLog>('/api/admin/operation-logs', {
      params: query,
    })
  },
  detail(id: number) {
    return httpClient.get<OperationLogDetail>(`/api/admin/operation-logs/${id}`)
  },
  clear(ids?: number[]) {
    return httpClient.delete<boolean>('/api/admin/operation-logs', {
      data: ids?.length ? { ids } : undefined,
    })
  },
}
