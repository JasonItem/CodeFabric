import { createFileRoute, redirect } from '@tanstack/react-router'
import { authApi } from '@/api/auth'
import { AuthenticatedLayout } from '@/components/layout/authenticated-layout'
import { canAccessRoute } from '@/lib/admin-menu'
import { useAuthStore } from '@/stores/auth-store'

export const Route = createFileRoute('/_authenticated')({
  beforeLoad: async ({ location }) => {
    const searchText = typeof location.search === 'string' ? location.search : ''
    const redirectTarget = `${location.pathname}${searchText}`
    let { auth } = useAuthStore.getState()
    if (!auth.accessToken) {
      throw redirect({
        to: '/sign-in',
        search: { redirect: redirectTarget },
      })
    }

    if (!auth.user) {
      try {
        const session = await authApi.me()
        auth.setSession(session)
        auth = useAuthStore.getState().auth
      } catch {
        auth.reset()
        throw redirect({
          to: '/sign-in',
          search: { redirect: redirectTarget },
        })
      }
    }

    const currentPath = location.pathname
    const hasAccess = canAccessRoute(currentPath, auth.menus)
    if (!hasAccess) {
      throw redirect({
        to: '/errors/$error',
        params: { error: 'unauthorized' },
      })
    }
  },
  component: AuthenticatedLayout,
})
