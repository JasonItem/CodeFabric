export type RoleRow = {
  id: number
  name: string
  code: string
  description?: string | null
  userCount: number
  permissionCount: number
  menuIds: number[]
  createdAt: string
}

export type CreateRolePayload = {
  name: string
  code: string
  description?: string
}

export type UpdateRolePayload = Partial<CreateRolePayload>

export type AssignPermissionsPayload = {
  menuIds: number[]
}
