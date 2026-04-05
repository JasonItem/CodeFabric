import { httpClient } from '@/api/http'
import type {
  CreateMenuPayload,
  MenuRow,
  UpdateMenuPayload,
} from '@/api/menu/types'

export const menuApi = {
  list() {
    return httpClient.get<MenuRow[]>('/api/admin/menus')
  },
  create(payload: CreateMenuPayload) {
    return httpClient.post<MenuRow, CreateMenuPayload>('/api/admin/menus', payload)
  },
  update(id: number, payload: UpdateMenuPayload) {
    return httpClient.put<MenuRow, UpdateMenuPayload>(`/api/admin/menus/${id}`, payload)
  },
  remove(id: number) {
    return httpClient.delete<boolean>(`/api/admin/menus/${id}`)
  },
}
