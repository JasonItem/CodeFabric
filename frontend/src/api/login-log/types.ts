export type LoginLogRow = {
  id: number
  userId: number | null
  username: string | null
  device: string | null
  browser: string | null
  os: string | null
  ip: string | null
  location: string | null
  userAgent: string | null
  success: boolean
  message: string | null
  createdAt: string
}

export type LoginLogQuery = {
  page: number
  pageSize: number
  ip?: string
  username?: string
  success?: string
  startTime?: string
  endTime?: string
}

export type PagedLoginLog = {
  list: LoginLogRow[]
  total: number
  page: number
  pageSize: number
}
