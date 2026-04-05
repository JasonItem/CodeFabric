import { create } from "zustand"
import { getCookie, removeCookie, setCookie } from "@/lib/cookies"
import { appConfig } from '@/config/app-config'

const ACCESS_TOKEN = appConfig.authTokenKey

export interface AdminMenu {
  id: number
  parentId: number | null
  name: string
  path: string | null
  component: string | null
  icon: string | null
  type: "DIRECTORY" | "MENU" | "BUTTON"
  sort: number
  visible: boolean
  permissionKey: string | null
}

export interface AuthUser {
  id: number
  username: string
  nickname: string
}

export interface RoleInfo {
  id: number
  name: string
  code: string
}

export interface AuthBundle {
  user: AuthUser
  roles: RoleInfo[]
  permissions: string[]
  menus: AdminMenu[]
}

interface AuthState {
  auth: {
    user: AuthUser | null
    roles: RoleInfo[]
    permissions: string[]
    menus: AdminMenu[]
    accessToken: string
    setSession: (bundle: AuthBundle) => void
    setAccessToken: (accessToken: string) => void
    resetAccessToken: () => void
    reset: () => void
  }
}

export const useAuthStore = create<AuthState>()((set) => {
  const cookieState = getCookie(ACCESS_TOKEN)
  let initToken = ""

  if (cookieState) {
    try {
      const parsed = JSON.parse(cookieState)
      initToken = typeof parsed === 'string' ? parsed : ''
    } catch {
      removeCookie(ACCESS_TOKEN)
      initToken = ''
    }
  }

  return {
    auth: {
      user: null,
      roles: [],
      permissions: [],
      menus: [],
      accessToken: initToken,
      setSession: (bundle) =>
        set((state) => ({
          ...state,
          auth: {
            ...state.auth,
            user: bundle.user,
            roles: bundle.roles,
            permissions: bundle.permissions,
            menus: bundle.menus,
          },
        })),
      setAccessToken: (accessToken) =>
        set((state) => {
          setCookie(ACCESS_TOKEN, JSON.stringify(accessToken))
          return { ...state, auth: { ...state.auth, accessToken } }
        }),
      resetAccessToken: () =>
        set((state) => {
          removeCookie(ACCESS_TOKEN)
          return { ...state, auth: { ...state.auth, accessToken: "" } }
        }),
      reset: () =>
        set((state) => {
          removeCookie(ACCESS_TOKEN)
          return {
            ...state,
            auth: {
              ...state.auth,
              user: null,
              roles: [],
              permissions: [],
              menus: [],
              accessToken: "",
            },
          }
        }),
    },
  }
})
