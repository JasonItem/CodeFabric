import {
  FileArchive,
  FileAudio2,
  FileCode2,
  FileImage,
  FileSpreadsheet,
  FileText,
  FileVideo,
  File as FileIcon,
} from 'lucide-react'
import type { ComponentType } from 'react'
import type { StoredFile } from '@/api/file/types'

type IconProps = { className?: string }

export function formatFileSize(size: number) {
  if (!Number.isFinite(size)) return '-'
  if (size < 1024) return `${size}B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)}KB`
  if (size < 1024 * 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)}MB`
  return `${(size / (1024 * 1024 * 1024)).toFixed(1)}GB`
}

export function getFileIcon(file: Pick<StoredFile, 'kind' | 'ext'>): ComponentType<IconProps> {
  if (file.kind === 'IMAGE') return FileImage
  if (file.kind === 'VIDEO') return FileVideo

  const ext = (file.ext || '').toLowerCase()
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return FileArchive
  if (['mp3', 'wav', 'flac', 'aac'].includes(ext)) return FileAudio2
  if (['xls', 'xlsx', 'csv'].includes(ext)) return FileSpreadsheet
  if (['md', 'txt', 'doc', 'docx', 'pdf', 'ppt', 'pptx'].includes(ext)) return FileText
  if (['js', 'ts', 'tsx', 'json', 'java', 'go', 'py', 'sql'].includes(ext)) return FileCode2
  return FileIcon
}

export function matchFileAccept(file: Pick<StoredFile, 'ext' | 'kind' | 'mimeType'>, accepts?: string[]) {
  if (!accepts || accepts.length === 0) return true

  const ext = `.${(file.ext || '').toLowerCase()}`
  const mime = (file.mimeType || '').toLowerCase()

  return accepts.some((accept) => {
    const rule = accept.trim().toLowerCase()
    if (!rule) return true
    if (rule.startsWith('.')) return rule === ext
    if (rule.endsWith('/*')) {
      const prefix = rule.slice(0, -1)
      if (mime.startsWith(prefix)) return true
      if (prefix === 'image/' && file.kind === 'IMAGE') return true
      if (prefix === 'video/' && file.kind === 'VIDEO') return true
      if (prefix === 'audio/' && ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg'].includes((file.ext || '').toLowerCase())) {
        return true
      }
      return false
    }
    return mime === rule
  })
}
