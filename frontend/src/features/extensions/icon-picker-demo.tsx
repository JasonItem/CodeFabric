import { useState } from 'react'
import { IconPicker, LucideIconByName } from '@/components/icon-picker'
import { Main } from '@/components/layout/main'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

export default function IconPickerDemoPage() {
  const [icon, setIcon] = useState('settings')

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>图标选择器</h1>
        <p className='text-muted-foreground'>支持关键字搜索、分页与移动端适配。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>基础示例</CardTitle>
          <CardDescription>可直接复用于菜单图标、按钮图标等场景。</CardDescription>
        </CardHeader>
        <CardContent className='space-y-4'>
          <div className='grid gap-4 md:grid-cols-2'>
            <div className='space-y-2'>
              <Label>当前图标</Label>
              <IconPicker value={icon} onValueChange={setIcon} />
            </div>
            <div className='space-y-2'>
              <Label>图标值（可手动输入）</Label>
              <Input value={icon} onChange={(e) => setIcon(e.target.value)} placeholder='请输入图标名' />
            </div>
          </div>

          <div className='flex items-center gap-3 rounded-md border p-4'>
            <LucideIconByName name={icon} className='h-6 w-6' />
            <span className='font-medium'>{icon || '无图标'}</span>
          </div>
        </CardContent>
      </Card>
    </Main>
  )
}
