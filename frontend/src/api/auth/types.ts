export type AuthUser = {
  id: number
  username: string
  nickname: string
}

export type RoleInfo = {
  id: number
  name: string
  code: string
}

export type AdminMenu = {
  id: number
  parentId: number | null
  name: string
  path: string | null
  component: string | null
  icon: string | null
  type: 'DIRECTORY' | 'MENU' | 'BUTTON'
  sort: number
  visible: boolean
  permissionKey: string | null
}

export type AuthBundle = {
  user: AuthUser
  roles: RoleInfo[]
  permissions: string[]
  menus: AdminMenu[]
}

export type LoginPayload = {
  username: string
  password: string
}

export type ChangePasswordPayload = {
  oldPassword: string
  newPassword: string
}
