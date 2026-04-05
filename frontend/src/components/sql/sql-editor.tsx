import Editor from '@monaco-editor/react'
import { useTheme } from '@/context/theme-provider'

type SqlEditorProps = {
  value: string
  onChange?: (value: string) => void
  height?: number | string
  readOnly?: boolean
  className?: string
}

export function SqlEditor({
  value,
  onChange,
  height = 320,
  readOnly = false,
  className,
}: SqlEditorProps) {
  const { theme } = useTheme()
  const monacoTheme = theme === 'dark' ? 'vs-dark' : 'vs'

  return (
    <div className={className}>
      <Editor
        language='sql'
        theme={monacoTheme}
        value={value}
        onChange={(next) => {
          if (readOnly) return
          onChange?.(next ?? '')
        }}
        options={{
          readOnly,
          minimap: { enabled: false },
          lineNumbers: 'on',
          roundedSelection: false,
          scrollBeyondLastLine: false,
          automaticLayout: true,
          wordWrap: 'on',
          fontSize: 13,
          tabSize: 2,
          glyphMargin: false,
          folding: true,
          quickSuggestions: true,
          suggestOnTriggerCharacters: true,
          formatOnPaste: true,
          formatOnType: true,
        }}
        height={height}
      />
    </div>
  )
}
