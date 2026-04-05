import { httpClient } from '@/api/http'
import type { LoginLogQuery, PagedLoginLog } from '@/api/login-log/types'

export const loginLogApi = {
  list(query: LoginLogQuery) {
    return httpClient.get<PagedLoginLog>('/api/admin/login-logs', {
      params: query,
    })
  },
  clear(ids?: number[]) {
    return httpClient.delete<boolean>('/api/admin/login-logs', {
      data: ids?.length ? { ids } : undefined,
    })
  },
}
