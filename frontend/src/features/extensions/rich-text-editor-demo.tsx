import { useState } from 'react'
import { RichTextEditor } from '@/components/rich-text-editor'
import { Main } from '@/components/layout/main'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const initialValue = `
<h2>CodeFabric 富文本示例</h2>
<p>这是一个基于 <strong>Tiptap</strong> 封装的富文本框，可用于公告、内容管理、邮件模板等场景。</p>
<ul>
  <li>支持标题、加粗、斜体、删除线</li>
  <li>支持有序/无序列表</li>
  <li>支持引用块与撤销重做</li>
</ul>
`

export default function RichTextEditorDemoPage() {
  const [content, setContent] = useState(initialValue.trim())

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>富文本框</h1>
        <p className='text-muted-foreground'>基于 Tiptap 封装的可复用富文本编辑组件。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>基础示例</CardTitle>
          <CardDescription>支持常见文本样式和结构化内容编辑。</CardDescription>
        </CardHeader>
        <CardContent className='space-y-4'>
          <RichTextEditor value={content} onChange={setContent} placeholder='请输入文章内容...' />
          <div className='rounded-md border bg-muted/20 p-3'>
            <div className='mb-2 text-xs text-muted-foreground'>HTML 输出</div>
            <pre className='max-h-56 overflow-auto whitespace-pre-wrap break-all text-xs'>{content}</pre>
          </div>
        </CardContent>
      </Card>
    </Main>
  )
}

