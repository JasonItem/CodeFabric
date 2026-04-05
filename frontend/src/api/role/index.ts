import { httpClient } from '@/api/http'
import type {
  AssignPermissionsPayload,
  CreateRolePayload,
  RoleRow,
  UpdateRolePayload,
} from '@/api/role/types'

export const roleApi = {
  list() {
    return httpClient.get<RoleRow[]>('/api/admin/roles')
  },
  create(payload: CreateRolePayload) {
    return httpClient.post<RoleRow, CreateRolePayload>('/api/admin/roles', payload)
  },
  update(id: number, payload: UpdateRolePayload) {
    return httpClient.put<RoleRow, UpdateRolePayload>(`/api/admin/roles/${id}`, payload)
  },
  remove(id: number) {
    return httpClient.delete<boolean>(`/api/admin/roles/${id}`)
  },
  assignPermissions(id: number, payload: AssignPermissionsPayload) {
    return httpClient.post<boolean, AssignPermissionsPayload>(
      `/api/admin/roles/${id}/permissions`,
      payload
    )
  },
}
