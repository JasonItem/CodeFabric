import { useState } from 'react'
import { DictSelect } from '@/components/dict-select'
import { Main } from '@/components/layout/main'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'

export default function DictSelectDemoPage() {
  const [status, setStatus] = useState('all')
  const [menuType, setMenuType] = useState('MENU')

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>字典选择器</h1>
        <p className='text-muted-foreground'>根据字典编码动态渲染下拉选项，常用于搜索和编辑表单。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>基础示例</CardTitle>
          <CardDescription>示例字典：user_status / menu_type</CardDescription>
        </CardHeader>
        <CardContent className='grid gap-4 md:grid-cols-2'>
          <div className='space-y-2'>
            <Label>用户状态（支持全部）</Label>
            <DictSelect dictCode='user_status' allowAll value={status} onValueChange={setStatus} />
            <p className='text-xs text-muted-foreground'>当前值：{status}</p>
          </div>

          <div className='space-y-2'>
            <Label>菜单类型</Label>
            <DictSelect dictCode='menu_type' value={menuType} onValueChange={setMenuType} />
            <p className='text-xs text-muted-foreground'>当前值：{menuType}</p>
          </div>
        </CardContent>
      </Card>
    </Main>
  )
}
