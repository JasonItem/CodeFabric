export type MenuRow = {
  id: number
  parentId: number | null
  name: string
  path: string | null
  component: string | null
  icon: string | null
  type: 'DIRECTORY' | 'MENU' | 'BUTTON'
  permissionKey: string | null
  sort: number
  visible: boolean
}

export type CreateMenuPayload = {
  parentId: number | null
  name: string
  path: string | null
  component: string | null
  icon: string | null
  type: 'DIRECTORY' | 'MENU' | 'BUTTON'
  permissionKey: string | null
  sort: number
  visible: boolean
}

export type UpdateMenuPayload = Partial<CreateMenuPayload>
