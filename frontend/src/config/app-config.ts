const env = import.meta.env

export const appConfig = {
  appName: env.VITE_APP_NAME || 'CodeFabric',
  appSubtitle: env.VITE_APP_SUBTITLE || 'Admin',
  appLogoIcon: env.VITE_APP_LOGO_ICON || 'command',
  authTokenKey: env.VITE_AUTH_TOKEN_KEY || 'admin_access_token',
  apiBaseUrl: env.VITE_API_BASE_URL || 'http://localhost:4000',
  apiResponseCodeField: env.VITE_API_RESPONSE_CODE_FIELD || 'code',
  apiResponseMessageField: env.VITE_API_RESPONSE_MESSAGE_FIELD || 'message',
  apiResponseDataField: env.VITE_API_RESPONSE_DATA_FIELD || 'data'
}
