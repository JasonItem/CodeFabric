import { type ComponentType, useEffect } from 'react'
import Placeholder from '@tiptap/extension-placeholder'
import StarterKit from '@tiptap/starter-kit'
import { EditorContent, useEditor } from '@tiptap/react'
import {
  Bold,
  Heading1,
  Heading2,
  Italic,
  List,
  ListOrdered,
  Quote,
  Redo2,
  Strikethrough,
  Undo2,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

type RichTextEditorProps = {
  value?: string
  onChange?: (html: string) => void
  placeholder?: string
  disabled?: boolean
  className?: string
  minHeight?: number
}

type ToolbarButton = {
  icon: ComponentType<{ className?: string }>
  label: string
  onClick: () => void
  isActive?: boolean
  disabled?: boolean
}

function ToolbarItem({ icon: Icon, label, onClick, isActive, disabled }: ToolbarButton) {
  return (
    <Button
      type='button'
      variant={isActive ? 'secondary' : 'ghost'}
      size='icon'
      className='h-8 w-8'
      onClick={onClick}
      disabled={disabled}
      title={label}
    >
      <Icon className='h-4 w-4' />
      <span className='sr-only'>{label}</span>
    </Button>
  )
}

export function RichTextEditor({
  value = '',
  onChange,
  placeholder = '请输入内容...',
  disabled = false,
  className,
  minHeight = 220,
}: RichTextEditorProps) {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Placeholder.configure({
        placeholder,
      }),
    ],
    content: value,
    editable: !disabled,
    immediatelyRender: false,
    editorProps: {
      attributes: {
        class:
          'ProseMirror focus:outline-none text-sm leading-6 min-h-[220px] px-3 py-3',
      },
    },
    onUpdate: ({ editor: instance }) => {
      onChange?.(instance.getHTML())
    },
  })

  useEffect(() => {
    if (!editor) return
    editor.setEditable(!disabled)
  }, [disabled, editor])

  useEffect(() => {
    if (!editor) return
    const current = editor.getHTML()
    if (current === value) return
    editor.commands.setContent(value || '', { emitUpdate: false })
  }, [editor, value])

  if (!editor) {
    return <div className='h-32 w-full animate-pulse rounded-md border bg-muted/40' />
  }

  return (
    <div className={cn('w-full rounded-md border bg-background', className)}>
      <div className='flex flex-wrap items-center gap-1 border-b p-2'>
        <ToolbarItem
          icon={Heading1}
          label='一级标题'
          onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
          isActive={editor.isActive('heading', { level: 1 })}
          disabled={disabled}
        />
        <ToolbarItem
          icon={Heading2}
          label='二级标题'
          onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
          isActive={editor.isActive('heading', { level: 2 })}
          disabled={disabled}
        />
        <ToolbarItem
          icon={Bold}
          label='加粗'
          onClick={() => editor.chain().focus().toggleBold().run()}
          isActive={editor.isActive('bold')}
          disabled={disabled}
        />
        <ToolbarItem
          icon={Italic}
          label='斜体'
          onClick={() => editor.chain().focus().toggleItalic().run()}
          isActive={editor.isActive('italic')}
          disabled={disabled}
        />
        <ToolbarItem
          icon={Strikethrough}
          label='删除线'
          onClick={() => editor.chain().focus().toggleStrike().run()}
          isActive={editor.isActive('strike')}
          disabled={disabled}
        />
        <ToolbarItem
          icon={List}
          label='无序列表'
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          isActive={editor.isActive('bulletList')}
          disabled={disabled}
        />
        <ToolbarItem
          icon={ListOrdered}
          label='有序列表'
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          isActive={editor.isActive('orderedList')}
          disabled={disabled}
        />
        <ToolbarItem
          icon={Quote}
          label='引用'
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
          isActive={editor.isActive('blockquote')}
          disabled={disabled}
        />
        <div className='ml-auto flex items-center gap-1'>
          <ToolbarItem
            icon={Undo2}
            label='撤销'
            onClick={() => editor.chain().focus().undo().run()}
            disabled={disabled || !editor.can().chain().focus().undo().run()}
          />
          <ToolbarItem
            icon={Redo2}
            label='重做'
            onClick={() => editor.chain().focus().redo().run()}
            disabled={disabled || !editor.can().chain().focus().redo().run()}
          />
        </div>
      </div>
      <div className='w-full'>
        <EditorContent
          editor={editor}
          className={cn(
            '[&_.ProseMirror_h1]:mb-2 [&_.ProseMirror_h1]:text-2xl [&_.ProseMirror_h1]:font-semibold',
            '[&_.ProseMirror_h2]:mb-2 [&_.ProseMirror_h2]:text-xl [&_.ProseMirror_h2]:font-semibold',
            '[&_.ProseMirror_p.is-editor-empty:first-child::before]:pointer-events-none',
            '[&_.ProseMirror_p.is-editor-empty:first-child::before]:float-left',
            '[&_.ProseMirror_p.is-editor-empty:first-child::before]:h-0',
            '[&_.ProseMirror_p.is-editor-empty:first-child::before]:text-muted-foreground',
            '[&_.ProseMirror_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)]',
            '[&_.ProseMirror_ul]:list-disc [&_.ProseMirror_ul]:pl-6',
            '[&_.ProseMirror_ol]:list-decimal [&_.ProseMirror_ol]:pl-6',
            '[&_.ProseMirror_blockquote]:border-l-2 [&_.ProseMirror_blockquote]:pl-3 [&_.ProseMirror_blockquote]:text-muted-foreground'
          )}
          style={{ minHeight }}
        />
      </div>
    </div>
  )
}
