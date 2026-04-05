/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL?: string
  readonly VITE_APP_NAME?: string
  readonly VITE_APP_SUBTITLE?: string
  readonly VITE_APP_LOGO_ICON?: string
  readonly VITE_AUTH_TOKEN_KEY?: string
  readonly VITE_APP_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
