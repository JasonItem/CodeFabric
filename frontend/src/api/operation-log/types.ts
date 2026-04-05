export type OperationLogRow = {
  id: number
  module: string | null
  action: string | null
  username: string | null
  ip: string | null
  location: string | null
  path: string
  method: string
  statusCode: number
  success: boolean
  message: string | null
  durationMs: number | null
  createdAt: string
}

export type OperationLogDetail = OperationLogRow & {
  userId: number | null
  userAgent: string | null
  requestBody: string | null
  responseBody: string | null
}

export type OperationLogQuery = {
  page: number
  pageSize: number
  path?: string
  module?: string
  username?: string
  success?: string
  startTime?: string
  endTime?: string
}

export type PagedOperationLog = {
  list: OperationLogRow[]
  total: number
  page: number
  pageSize: number
}
