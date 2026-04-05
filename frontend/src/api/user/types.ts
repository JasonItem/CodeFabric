export type UserRole = {
  id: number
  name: string
  code: string
}

export type User = {
  id: number
  username: string
  nickname: string
  status: 'ACTIVE' | 'DISABLED'
  createdAt: string
  roles: UserRole[]
}

export type UserQueryRole = {
  id: number
  name: string
  code: string
}

export type CreateUserPayload = {
  username: string
  nickname: string
  password: string
  status: 'ACTIVE' | 'DISABLED'
  roleIds: number[]
}

export type UpdateUserPayload = Partial<CreateUserPayload>
