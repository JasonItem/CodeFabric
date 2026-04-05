import { httpClient } from '@/api/http'
import type {
  CreateDictItemPayload,
  CreateDictTypePayload,
  DictItemRow,
  DictOption,
  DictTypeRow,
  UpdateDictItemPayload,
  UpdateDictTypePayload,
} from '@/api/dictionary/types'

export const dictionaryApi = {
  listTypes(keyword?: string) {
    return httpClient.get<DictTypeRow[]>('/api/admin/dictionaries/types', {
      params: keyword ? { keyword } : undefined,
    })
  },
  createType(payload: CreateDictTypePayload) {
    return httpClient.post<DictTypeRow, CreateDictTypePayload>(
      '/api/admin/dictionaries/types',
      payload
    )
  },
  updateType(id: number, payload: UpdateDictTypePayload) {
    return httpClient.put<DictTypeRow, UpdateDictTypePayload>(
      `/api/admin/dictionaries/types/${id}`,
      payload
    )
  },
  deleteType(id: number) {
    return httpClient.delete<boolean>(`/api/admin/dictionaries/types/${id}`)
  },
  listItems(typeId: number, keyword?: string) {
    return httpClient.get<DictItemRow[]>(`/api/admin/dictionaries/types/${typeId}/items`, {
      params: keyword ? { keyword } : undefined,
    })
  },
  createItem(typeId: number, payload: CreateDictItemPayload) {
    return httpClient.post<DictItemRow, CreateDictItemPayload>(
      `/api/admin/dictionaries/types/${typeId}/items`,
      payload
    )
  },
  updateItem(id: number, payload: UpdateDictItemPayload) {
    return httpClient.put<DictItemRow, UpdateDictItemPayload>(
      `/api/admin/dictionaries/items/${id}`,
      payload
    )
  },
  deleteItem(id: number) {
    return httpClient.delete<boolean>(`/api/admin/dictionaries/items/${id}`)
  },
  optionsByCode(code: string) {
    return httpClient.get<DictOption[]>(`/api/admin/dictionaries/options/${code}`)
  },
}
