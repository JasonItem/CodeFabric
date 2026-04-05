import { type ReactNode } from "react"
import { useAuthStore } from "@/stores/auth-store"

export function PermissionGate({
  permission,
  children,
}: {
  permission: string
  children: ReactNode
}) {
  const { auth } = useAuthStore()
  if (!auth.permissions.includes(permission)) return null
  return <>{children}</>
}
