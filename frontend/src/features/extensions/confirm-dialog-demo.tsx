import { useState } from 'react'
import { toast } from 'sonner'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function ConfirmDialogDemoPage() {
  const [open, setOpen] = useState(false)

  return (
    <Main className='space-y-4'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>确认弹窗</h1>
        <p className='text-muted-foreground'>二次确认操作组件，支持危险操作样式。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>删除确认示例</CardTitle>
          <CardDescription>点击按钮触发确认弹窗。</CardDescription>
        </CardHeader>
        <CardContent>
          <Button variant='destructive' onClick={() => setOpen(true)}>
            打开确认弹窗
          </Button>
        </CardContent>
      </Card>

      <ConfirmDialog
        open={open}
        onOpenChange={setOpen}
        title='确认删除记录？'
        desc='该操作不可恢复，确认后将永久删除。'
        destructive
        handleConfirm={() => {
          toast.success('删除成功（示例）')
          setOpen(false)
        }}
      />
    </Main>
  )
}
