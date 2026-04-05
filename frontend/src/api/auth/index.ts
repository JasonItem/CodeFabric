import { httpClient } from '@/api/http'
import type {
  AuthBundle,
  ChangePasswordPayload,
  LoginPayload,
} from '@/api/auth/types'

export const authApi = {
  login(payload: LoginPayload) {
    return httpClient.post<AuthBundle, LoginPayload>(
      '/api/admin/auth/login',
      payload,
      {
        // 登录页由表单侧统一提示，避免和 http 全局成功/失败提示重复
        silentSuccessToast: true,
        silentErrorToast: true,
      }
    )
  },
  logout() {
    return httpClient.post<boolean>('/api/admin/auth/logout')
  },
  me() {
    return httpClient.get<AuthBundle>('/api/admin/auth/me')
  },
  changePassword(payload: ChangePasswordPayload) {
    return httpClient.post<boolean, ChangePasswordPayload>(
      '/api/admin/auth/change-password',
      payload
    )
  },
}
