import axios, {
  type AxiosError,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios'
import { toast } from 'sonner'
import { appConfig } from '@/config/app-config'
import { ApiCode, isApiSuccess } from '@/constants/api-code'
import { useAuthStore } from '@/stores/auth-store'

export type ApiResult<T> = {
  code?: number
  message?: string
  data?: T
  success?: boolean
}

export const API_BASE_URL = appConfig.apiBaseUrl

type RequestConfig = AxiosRequestConfig & {
  silentErrorToast?: boolean
  silentSuccessToast?: boolean
  successMessage?: string
}

const http = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

function redirectToSignInIfNeeded() {
  if (typeof window === 'undefined') return
  if (window.location.pathname.startsWith('/sign-in')) return
  const redirect = `${window.location.pathname}${window.location.search}`
  window.location.href = `/sign-in?redirect=${encodeURIComponent(redirect)}`
}

http.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error: AxiosError<ApiResult<unknown>>) => {
    const status = error.response?.status
    const requestUrl = error.config?.url || ''
    const customConfig = (error.config || {}) as RequestConfig

    if (status === ApiCode.UNAUTHORIZED && !requestUrl.includes('/api/admin/auth/login')) {
      useAuthStore.getState().auth.reset()
      redirectToSignInIfNeeded()
    }

    const messageKey = appConfig.apiResponseMessageField as keyof ApiResult<unknown>
    const payload = error.response?.data
    const payloadMessage =
      payload && typeof payload === 'object'
        ? String(payload[messageKey] ?? payload.message ?? '')
        : ''

    const message =
      payloadMessage ||
      (status === ApiCode.FORBIDDEN
        ? '没有权限执行该操作'
        : status === ApiCode.NOT_FOUND
          ? '请求资源不存在'
          : status && status >= ApiCode.SERVER_ERROR
            ? '服务器异常，请稍后重试'
            : error.message || '请求失败')

    if (!customConfig.silentErrorToast) {
      toast.error(message)
    }

    return Promise.reject(new Error(message))
  }
)

async function request<T>(config: RequestConfig): Promise<T> {
  const response = await http.request<ApiResult<T>>(config)
  const payload = response.data
  const codeKey = appConfig.apiResponseCodeField as keyof ApiResult<T>
  const dataKey = appConfig.apiResponseDataField as keyof ApiResult<T>
  const messageKey = appConfig.apiResponseMessageField as keyof ApiResult<T>

  if (!payload || typeof payload !== 'object') {
    throw new Error('响应格式错误')
  }

  // 新协议：{ code, message, data }
  const codeValue = payload[codeKey]
  if (typeof codeValue === 'number') {
    if (!isApiSuccess(codeValue)) {
      const message = String(payload[messageKey] ?? '请求失败')
      if (!config.silentErrorToast) {
        toast.error(message)
      }
      throw new Error(message)
    }

    const method = String(config.method || 'GET').toUpperCase()
    const isReadMethod = ['GET', 'HEAD', 'OPTIONS'].includes(method)
    if (!isReadMethod && !config.silentSuccessToast) {
      const rawMsg = String(payload[messageKey] ?? '')
      const okMsg = config.successMessage || (rawMsg && rawMsg !== 'ok' ? rawMsg : '操作成功')
      toast.success(okMsg)
    }

    return payload[dataKey] as T
  }

  // 旧协议兼容：{ success, message, data }
  if (!payload?.success) {
    const message = String(payload?.message || '请求失败')
    if (!config.silentErrorToast) {
      toast.error(message)
    }
    throw new Error(message)
  }

  const method = String(config.method || 'GET').toUpperCase()
  const isReadMethod = ['GET', 'HEAD', 'OPTIONS'].includes(method)
  if (!isReadMethod && !config.silentSuccessToast) {
    const rawMsg = String(payload?.message || '')
    const okMsg = config.successMessage || (rawMsg && rawMsg !== 'ok' ? rawMsg : '操作成功')
    toast.success(okMsg)
  }

  return payload.data as T
}

export const httpClient = {
  request,
  get<T>(url: string, config?: RequestConfig) {
    return request<T>({ ...(config || {}), method: 'GET', url })
  },
  post<T, D = unknown>(url: string, data?: D, config?: RequestConfig) {
    return request<T>({ ...(config || {}), method: 'POST', url, data })
  },
  put<T, D = unknown>(url: string, data?: D, config?: RequestConfig) {
    return request<T>({ ...(config || {}), method: 'PUT', url, data })
  },
  delete<T>(url: string, config?: RequestConfig) {
    return request<T>({ ...(config || {}), method: 'DELETE', url })
  },
}
