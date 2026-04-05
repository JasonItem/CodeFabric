import { useState } from 'react'
import { Main } from '@/components/layout/main'
import { PasswordInput } from '@/components/password-input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'

export default function PasswordInputDemoPage() {
  const [password, setPassword] = useState('')

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>密码输入框</h1>
        <p className='text-muted-foreground'>支持密码显隐切换，适用于登录和修改密码场景。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>基础示例</CardTitle>
          <CardDescription>默认隐藏密码，可点击右侧按钮切换显示状态。</CardDescription>
        </CardHeader>
        <CardContent className='max-w-md space-y-3'>
          <Label>新密码</Label>
          <PasswordInput
            placeholder='请输入新密码'
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
          <p className='text-xs text-muted-foreground'>当前输入长度：{password.length}</p>
        </CardContent>
      </Card>
    </Main>
  )
}
