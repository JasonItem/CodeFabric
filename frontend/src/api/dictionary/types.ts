export type DictTypeRow = {
  id: number
  name: string
  code: string
  description: string | null
  status: boolean
  sort: number
  createdAt: string
  itemCount: number
}

export type DictItemRow = {
  id: number
  dictTypeId: number
  label: string
  value: string
  tagType: string | null
  tagClass: string | null
  status: boolean
  sort: number
  createdAt: string
}

export type DictOption = {
  id: number
  label: string
  value: string
  tagType: string | null
  tagClass: string | null
  sort: number
}

export type CreateDictTypePayload = {
  name: string
  code: string
  description: string | null
  status: boolean
  sort: number
}

export type UpdateDictTypePayload = Partial<CreateDictTypePayload>

export type CreateDictItemPayload = {
  label: string
  value: string
  tagType: string | null
  tagClass: string | null
  status: boolean
  sort: number
}

export type UpdateDictItemPayload = Partial<CreateDictItemPayload>
