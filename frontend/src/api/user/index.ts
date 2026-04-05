import { httpClient } from '@/api/http'
import type {
  CreateUserPayload,
  UpdateUserPayload,
  User,
  UserQueryRole,
} from '@/api/user/types'

export const userApi = {
  list() {
    return httpClient.get<User[]>('/api/admin/users')
  },
  create(payload: CreateUserPayload) {
    return httpClient.post<User, CreateUserPayload>('/api/admin/users', payload)
  },
  update(id: number, payload: UpdateUserPayload) {
    return httpClient.put<User, UpdateUserPayload>(`/api/admin/users/${id}`, payload)
  },
  remove(id: number) {
    return httpClient.delete<boolean>(`/api/admin/users/${id}`)
  },
  roleOptions() {
    return httpClient.get<UserQueryRole[]>('/api/admin/roles')
  },
}
