import { lazy, type ComponentType, type LazyExoticComponent } from 'react'

type PageModule = Record<string, unknown> & {
  default?: ComponentType
}

const featureModules = import.meta.glob<PageModule>('/src/features/**/*.tsx')
const componentImporters = new Map<string, () => Promise<PageModule>>()
const lazyCache = new Map<string, LazyExoticComponent<ComponentType>>()

function normalizeKey(value: string) {
  return value
    .trim()
    .replace(/^\/+/, '')
    .replace(/\.tsx?$/i, '')
    .replace(/\/+$/, '')
    .toLowerCase()
}

function toPascalCase(value: string) {
  return value
    .split(/[^a-zA-Z0-9]+/)
    .filter(Boolean)
    .map((part) => part[0]!.toUpperCase() + part.slice(1))
    .join('')
}

function extractFeatureKey(filePath: string) {
  return normalizeKey(
    filePath.replace('/src/features/', '').replace(/\.tsx$/, '')
  )
}

function registerComponentKey(key: string, importer: () => Promise<PageModule>) {
  if (!key) return
  if (!componentImporters.has(key)) {
    componentImporters.set(key, importer)
  }
}

function resolveModuleExport(module: PageModule, key: string): ComponentType | null {
  if (typeof module.default === 'function') return module.default

  const lastSegment = key.split('/').pop() || ''
  const candidates = [toPascalCase(lastSegment), `${toPascalCase(lastSegment)}Page`]

  for (const candidate of candidates) {
    const matched = module[candidate]
    if (typeof matched === 'function') return matched as ComponentType
  }

  for (const value of Object.values(module)) {
    if (typeof value === 'function') return value as ComponentType
  }

  return null
}

for (const [filePath, importer] of Object.entries(featureModules)) {
  const key = extractFeatureKey(filePath)
  registerComponentKey(key, importer)

  if (key.endsWith('/index')) {
    registerComponentKey(key.slice(0, -'/index'.length), importer)
  }

  if (key.endsWith('-page')) {
    registerComponentKey(key.slice(0, -'-page'.length), importer)
  }
}

export function resolveMenuComponentByKey(component?: string | null) {
  if (!component) return null

  const normalized = normalizeKey(component)
  const importer = componentImporters.get(normalized)
  if (!importer) return null

  if (lazyCache.has(normalized)) {
    return lazyCache.get(normalized)!
  }

  const lazyComponent = lazy(async () => {
    const module = await importer()
    const resolved = resolveModuleExport(module, normalized)
    if (!resolved) {
      throw new Error(`菜单组件未导出可渲染组件: ${component}`)
    }

    return { default: resolved }
  })

  lazyCache.set(normalized, lazyComponent)
  return lazyComponent
}

const pathFallbackMap: Record<string, string> = {
  '/': 'dashboard/index',
  '/users': 'rbac/users',
  '/roles': 'rbac/roles',
  '/menus': 'rbac/menus',
  '/dictionaries': 'rbac/dictionaries',
  '/files': 'rbac/files-page',
  '/login-logs': 'rbac/login-logs',
  '/operation-logs': 'rbac/operation-logs',
  '/components/date-time-picker': 'extensions/date-time-picker-demo',
  '/components/icon-picker': 'extensions/icon-picker-demo',
  '/components/dict-select': 'extensions/dict-select-demo',
  '/components/dict-display': 'extensions/dict-display-demo',
  '/components/confirm-dialog': 'extensions/confirm-dialog-demo',
  '/components/password-input': 'extensions/password-input-demo',
  '/components/long-text': 'extensions/long-text-demo',
  '/components/rich-text-editor': 'extensions/rich-text-editor-demo',
  '/components/file-picker': 'extensions/file-picker-demo',
  '/components/media-preview': 'extensions/media-preview-demo',
  '/table-manager': 'dev-tools/table-manager',
}

export function resolveMenuComponentFallbackByPath(path?: string | null) {
  if (!path) return null
  const normalizedPath = path.trim().replace(/\/+$/, '') || '/'
  const key = pathFallbackMap[normalizedPath]
  if (!key) return null
  return resolveMenuComponentByKey(key)
}
