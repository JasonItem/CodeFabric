import { API_BASE_URL, httpClient } from '@/api/http'
import type {
  CreateFolderPayload,
  FileFolder,
  FileListQuery,
  FileListResult,
  FileSource,
  StoredFile,
  UpdateFilePayload,
  UpdateFolderPayload,
} from './types'

function toAbsoluteFileUrl(url: string) {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) return url
  return `${API_BASE_URL}${url.startsWith('/') ? url : `/${url}`}`
}

function normalizeFileUrl(file: StoredFile) {
  return { ...file, url: toAbsoluteFileUrl(file.url) }
}

export const fileApi = {
  listFolders() {
    return httpClient.get<FileFolder[]>('/api/admin/files/folders')
  },
  createFolder(payload: CreateFolderPayload) {
    return httpClient.post<FileFolder, CreateFolderPayload>('/api/admin/files/folders', payload)
  },
  updateFolder(id: number, payload: UpdateFolderPayload) {
    return httpClient.put<FileFolder, UpdateFolderPayload>(`/api/admin/files/folders/${id}`, payload)
  },
  removeFolder(id: number, moveTo?: number | null) {
    const params =
      moveTo === undefined ? undefined : { moveTo: moveTo === null ? 'ROOT' : String(moveTo) }
    return httpClient.delete<boolean>(`/api/admin/files/folders/${id}`, { params })
  },
  async listFiles(query: FileListQuery) {
    const data = await httpClient.get<FileListResult>('/api/admin/files', { params: query })
    return { ...data, list: data.list.map(normalizeFileUrl) }
  },
  async uploadFiles(params: {
    files: File[]
    folderId?: number | null
    source?: FileSource
  }) {
    const formData = new FormData()
    params.files.forEach((file) => formData.append('files[]', file))
    if (params.folderId) formData.append('folderId', String(params.folderId))
    if (params.source) formData.append('source', params.source)
    const uploaded = await httpClient.post<StoredFile[]>(
      '/api/admin/files/upload',
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      }
    )
    return uploaded.map(normalizeFileUrl)
  },
  async updateFile(id: number, payload: UpdateFilePayload) {
    const file = await httpClient.put<StoredFile, UpdateFilePayload>(`/api/admin/files/${id}`, payload)
    return normalizeFileUrl(file)
  },
  removeFile(id: number) {
    return httpClient.delete<boolean>(`/api/admin/files/${id}`)
  },
  removeFiles(ids: number[]) {
    return httpClient.post<boolean, { ids: number[] }>('/api/admin/files/batch-delete', { ids })
  },
}
