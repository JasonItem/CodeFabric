import { httpClient } from '@/api/http'
  import type {
  CreateSqlResponse,
  TableColumnRow,
  TableExportResponse,
  TableExportAllResponse,
  TableForeignKeyRow,
  TableImportResponse,
  TableIndexRow,
  TableListResponse,
} from '@/api/table/types'

export const tableApi = {
  list(params: { page?: number; pageSize?: number; keyword?: string } = {}) {
    const search = new URLSearchParams()
    if (params.page) search.set('page', String(params.page))
    if (params.pageSize) search.set('pageSize', String(params.pageSize))
    if (params.keyword?.trim()) search.set('keyword', params.keyword.trim())

    const query = search.toString()
    return httpClient.get<TableListResponse>(`/api/admin/tables${query ? `?${query}` : ''}`)
  },

  columns(tableName: string) {
    return httpClient.get<TableColumnRow[]>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/columns`
    )
  },

  createSql(tableName: string) {
    return httpClient.get<CreateSqlResponse>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/create-sql`
    )
  },

  indexes(tableName: string) {
    return httpClient.get<TableIndexRow[]>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/indexes`
    )
  },

  foreignKeys(tableName: string) {
    return httpClient.get<TableForeignKeyRow[]>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/foreign-keys`
    )
  },

  createBySql(sql: string) {
    return httpClient.post<boolean, { sql: string }>('/api/admin/tables/create', { sql })
  },

  alterBySql(sql: string) {
    return httpClient.post<boolean, { sql: string }>('/api/admin/tables/alter', { sql })
  },

  remove(tableName: string) {
    return httpClient.delete<boolean>(`/api/admin/tables/${encodeURIComponent(tableName)}`)
  },

  truncate(tableName: string) {
    return httpClient.post<boolean, undefined>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/truncate`
    )
  },

  exportData(tableName: string) {
    return httpClient.get<TableExportResponse>(
      `/api/admin/tables/${encodeURIComponent(tableName)}/export`
    )
  },

  exportAllData() {
    return httpClient.get<TableExportAllResponse>('/api/admin/tables/export-all')
  },

  importSqlFile(file: File, mode: 'strict' | 'skip-create' = 'skip-create') {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('mode', mode)
    return httpClient.post<TableImportResponse, FormData>(
      '/api/admin/tables/import',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )
  },
}
