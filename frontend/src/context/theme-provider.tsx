import { createContext, useContext, useEffect, useState, useMemo } from 'react'
import { getCookie, setCookie, removeCookie } from '@/lib/cookies'
import {
  DEFAULT_THEME_PRESET,
  findThemePreset,
  getThemePresetVariableNames,
  THEME_PRESETS,
  type ResolvedThemeMode,
} from '@/styles/theme/presets'

type Theme = 'dark' | 'light' | 'system'
type ResolvedTheme = Exclude<Theme, 'system'>

const DEFAULT_THEME = 'system'
const THEME_COOKIE_NAME = 'vite-ui-theme'
const THEME_PRESET_COOKIE_NAME = 'vite-ui-color-theme'
const THEME_COOKIE_MAX_AGE = 60 * 60 * 24 * 365 // 1 year

type ThemeProviderProps = {
  children: React.ReactNode
  defaultTheme?: Theme
  storageKey?: string
}

type ThemeProviderState = {
  defaultTheme: Theme
  defaultThemePreset: string
  themePreset: string
  resolvedTheme: ResolvedTheme
  theme: Theme
  availableThemePresets: Array<{ key: string; label: string; description?: string }>
  setTheme: (theme: Theme) => void
  setThemePreset: (preset: string) => void
  resetTheme: () => void
  resetThemePreset: () => void
}

const initialState: ThemeProviderState = {
  defaultTheme: DEFAULT_THEME,
  defaultThemePreset: DEFAULT_THEME_PRESET,
  themePreset: DEFAULT_THEME_PRESET,
  resolvedTheme: 'light',
  theme: DEFAULT_THEME,
  availableThemePresets: THEME_PRESETS.map((item) => ({
    key: item.key,
    label: item.label,
    description: item.description,
  })),
  setTheme: () => null,
  setThemePreset: () => null,
  resetTheme: () => null,
  resetThemePreset: () => null,
}

const ThemeContext = createContext<ThemeProviderState>(initialState)

export function ThemeProvider({
  children,
  defaultTheme = DEFAULT_THEME,
  storageKey = THEME_COOKIE_NAME,
  ...props
}: ThemeProviderProps) {
  const [theme, _setTheme] = useState<Theme>(
    () => (getCookie(storageKey) as Theme) || defaultTheme
  )
  const [themePreset, _setThemePreset] = useState<string>(() => {
    const cookieValue = getCookie(THEME_PRESET_COOKIE_NAME)
    if (!cookieValue) return DEFAULT_THEME_PRESET
    return findThemePreset(cookieValue).key
  })

  // Optimized: Memoize the resolved theme calculation to prevent unnecessary re-computations
  const resolvedTheme = useMemo((): ResolvedTheme => {
    if (theme === 'system') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light'
    }
    return theme as ResolvedTheme
  }, [theme])

  useEffect(() => {
    const root = window.document.documentElement
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

    const applyTheme = (currentResolvedTheme: ResolvedTheme) => {
      root.classList.remove('light', 'dark') // Remove existing theme classes
      root.classList.add(currentResolvedTheme) // Add the new theme class
    }

    const handleChange = () => {
      if (theme === 'system') {
        const systemTheme = mediaQuery.matches ? 'dark' : 'light'
        applyTheme(systemTheme)
      }
    }

    applyTheme(resolvedTheme)

    mediaQuery.addEventListener('change', handleChange)

    return () => mediaQuery.removeEventListener('change', handleChange)
  }, [theme, resolvedTheme])

  useEffect(() => {
    const root = window.document.documentElement
    const clearVariables = () => {
      for (const variableName of getThemePresetVariableNames()) {
        root.style.removeProperty(variableName)
      }
    }

    const preset = findThemePreset(themePreset)
    if (preset.key === DEFAULT_THEME_PRESET) {
      clearVariables()
      root.dataset.colorTheme = DEFAULT_THEME_PRESET
      return
    }

    const mode: ResolvedThemeMode = resolvedTheme
    const scopedVars = preset.variables[mode]
    clearVariables()
    Object.entries(scopedVars).forEach(([name, value]) => {
      root.style.setProperty(name, value)
    })
    root.dataset.colorTheme = preset.key
  }, [resolvedTheme, themePreset])

  const setTheme = (theme: Theme) => {
    setCookie(storageKey, theme, THEME_COOKIE_MAX_AGE)
    _setTheme(theme)
  }

  const setThemePreset = (preset: string) => {
    const nextPreset = findThemePreset(preset).key
    setCookie(THEME_PRESET_COOKIE_NAME, nextPreset, THEME_COOKIE_MAX_AGE)
    _setThemePreset(nextPreset)
  }

  const resetTheme = () => {
    removeCookie(storageKey)
    removeCookie(THEME_PRESET_COOKIE_NAME)
    _setTheme(DEFAULT_THEME)
    _setThemePreset(DEFAULT_THEME_PRESET)
  }

  const resetThemePreset = () => {
    removeCookie(THEME_PRESET_COOKIE_NAME)
    _setThemePreset(DEFAULT_THEME_PRESET)
  }

  const contextValue = {
    availableThemePresets: THEME_PRESETS.map((item) => ({
      key: item.key,
      label: item.label,
      description: item.description,
    })),
    defaultTheme,
    defaultThemePreset: DEFAULT_THEME_PRESET,
    resolvedTheme,
    resetTheme,
    resetThemePreset,
    theme,
    themePreset,
    setTheme,
    setThemePreset,
  }

  return (
    <ThemeContext value={contextValue} {...props}>
      {children}
    </ThemeContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useTheme = () => {
  const context = useContext(ThemeContext)

  if (!context) throw new Error('useTheme must be used within a ThemeProvider')

  return context
}
