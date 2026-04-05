import theme1Css from './theme1.css?raw'
import theme2Css from './theme2.css?raw'
import theme3Css from './theme3.css?raw'
import theme4Css from './theme4.css?raw'
import theme5Css from './theme5.css?raw'
import theme6Css from './theme6.css?raw'
import theme7Css from './theme7.css?raw'
import theme8Css from './theme8.css?raw'

export type ResolvedThemeMode = 'light' | 'dark'

export type ThemePreset = {
  key: string
  label: string
  description?: string
  variables: Record<ResolvedThemeMode, Record<string, string>>
}

function parseVariableBlock(cssText: string, selector: ':root' | '.dark') {
  const pattern =
    selector === ':root'
      ? /:root\s*\{([\s\S]*?)\}/m
      : /\.dark\s*\{([\s\S]*?)\}/m
  const match = cssText.match(pattern)
  const block = match?.[1] ?? ''
  const vars: Record<string, string> = {}
  const varPattern = /(--[a-zA-Z0-9-_]+)\s*:\s*([^;]+);/g

  let item = varPattern.exec(block)
  while (item) {
    vars[item[1].trim()] = item[2].trim()
    item = varPattern.exec(block)
  }
  return vars
}

function createPreset(key: string, label: string, cssText: string): ThemePreset {
  return {
    key,
    label,
    variables: {
      light: parseVariableBlock(cssText, ':root'),
      dark: parseVariableBlock(cssText, '.dark'),
    },
  }
}

export const THEME_PRESETS: ThemePreset[] = [
  createPreset('default', '默认', ''),
  createPreset('theme1', '主题一', theme1Css),
  createPreset('theme2', '主题二', theme2Css),
  createPreset('theme3','主题三',theme3Css),
  createPreset('theme4','主题四',theme4Css),
  createPreset('theme5','主题五',theme5Css),
  createPreset('theme6','主题六',theme6Css),
  createPreset('theme7','主题七',theme7Css),
  createPreset('theme8','主题八',theme8Css)
]

export const DEFAULT_THEME_PRESET = 'default'

export function findThemePreset(presetKey: string) {
  return THEME_PRESETS.find((item) => item.key === presetKey) ?? THEME_PRESETS[0]
}

export function getThemePresetVariableNames() {
  const names = new Set<string>()
  for (const preset of THEME_PRESETS) {
    Object.keys(preset.variables.light).forEach((name) => names.add(name))
    Object.keys(preset.variables.dark).forEach((name) => names.add(name))
  }
  return Array.from(names)
}

