import { DictDisplay } from '@/components/dict-display'
import { Main } from '@/components/layout/main'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const rows = [
  { id: 1, user: 'admin', status: 'ACTIVE', menuType: 'DIRECTORY' },
  { id: 2, user: 'ops', status: 'INACTIVE', menuType: 'MENU' },
  { id: 3, user: 'guest', status: 'BANNED', menuType: 'BUTTON' },
]

export default function DictDisplayDemoPage() {
  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>字典显示器</h1>
        <p className='text-muted-foreground'>根据字典值渲染文本或标签，可配置不同值对应样式。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>表格渲染示例</CardTitle>
          <CardDescription>示例字典：user_status / menu_type</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>编号</TableHead>
                <TableHead>用户</TableHead>
                <TableHead>状态（标签）</TableHead>
                <TableHead>菜单类型（标签）</TableHead>
                <TableHead>状态（文本）</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((row) => (
                <TableRow key={row.id}>
                  <TableCell>{row.id}</TableCell>
                  <TableCell>{row.user}</TableCell>
                  <TableCell>
                    <DictDisplay dictCode='user_status' value={row.status} mode='badge' />
                  </TableCell>
                  <TableCell>
                    <DictDisplay dictCode='menu_type' value={row.menuType} mode='badge' />
                  </TableCell>
                  <TableCell>
                    <DictDisplay dictCode='user_status' value={row.status} mode='text' />
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
