import { LongText } from '@/components/long-text'
import { Main } from '@/components/layout/main'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const mockRows = [
  {
    id: 1,
    title: '这是一个很长很长的菜单标题，用于演示桌面端 Tooltip 与移动端 Popover 的兼容展示效果',
  },
  {
    id: 2,
    title: '另一条很长的描述文本：支持自动判断是否溢出，仅在溢出时显示辅助浮层。',
  },
]

export default function LongTextDemoPage() {
  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>长文本组件</h1>
        <p className='text-muted-foreground'>文本溢出时自动展示完整内容，桌面端 Tooltip、移动端 Popover。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>表格场景示例</CardTitle>
          <CardDescription>单元格宽度受限时自动省略。</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className='w-20'>编号</TableHead>
                <TableHead>标题</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockRows.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>{item.id}</TableCell>
                  <TableCell className='max-w-[320px]'>
                    <LongText>{item.title}</LongText>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </Main>
  )
}
