export type FileSource = 'ADMIN' | 'USER'
export type FileKind = 'IMAGE' | 'VIDEO' | 'FILE'

export type FileFolder = {
  id: number
  parentId: number | null
  name: string
  sort: number
  createdAt: string
  updatedAt: string
}

export type StoredFile = {
  id: number
  folderId: number | null
  source: FileSource
  kind: FileKind
  name: string
  originalName: string
  ext: string | null
  mimeType: string | null
  size: number
  relativePath: string
  url: string
  createdById: number | null
  createdByName: string | null
  createdAt: string
  updatedAt: string
  folder?: FileFolder | null
}

export type FileListQuery = {
  page?: number
  pageSize?: number
  folderId?: number | null
  keyword?: string
  source?: FileSource | ''
  kind?: FileKind | ''
  startAt?: string
  endAt?: string
}

export type FileListResult = {
  list: StoredFile[]
  total: number
  page: number
  pageSize: number
}

export type CreateFolderPayload = {
  parentId?: number | null
  name: string
  sort?: number
}

export type UpdateFolderPayload = Partial<CreateFolderPayload>

export type UpdateFilePayload = {
  folderId?: number | null
  name?: string
}

