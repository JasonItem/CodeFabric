import { useState } from 'react'
import { KeyRound } from 'lucide-react'
import useDialogState from '@/hooks/use-dialog-state'
import { useAuthStore } from '@/stores/auth-store'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { ChangePasswordDialog } from '@/components/change-password-dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { SignOutDialog } from '@/components/sign-out-dialog'

export function ProfileDropdown() {
  const [open, setOpen] = useDialogState()
  const [changePasswordOpen, setChangePasswordOpen] = useState(false)
  const { auth } = useAuthStore()
  const nickname = auth.user?.nickname || '管理员'
  const username = auth.user?.username || 'admin'
  const canChangePassword = auth.permissions.includes('system:auth:change-password')

  return (
    <>
      <DropdownMenu modal={false}>
        <DropdownMenuTrigger asChild>
          <Button variant='ghost' className='relative h-8 w-8 rounded-full'>
              <Avatar className='h-8 w-8'>
                <AvatarImage src='/avatars/01.png' alt='@shadcn' />
                <AvatarFallback>SN</AvatarFallback>
              </Avatar>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className='w-56' align='end' forceMount>
          <DropdownMenuLabel className='font-normal'>
            <div className='flex flex-col gap-1.5'>
              <p className='text-sm leading-none font-medium'>{nickname}</p>
              <p className='text-xs leading-none text-muted-foreground'>
                {username}
              </p>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          {canChangePassword && (
            <>
              <DropdownMenuItem onClick={() => setChangePasswordOpen(true)}>
                <KeyRound />
                修改密码
              </DropdownMenuItem>
              <DropdownMenuSeparator />
            </>
          )}
          <DropdownMenuItem variant='destructive' onClick={() => setOpen(true)}>
            退出登录
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <ChangePasswordDialog
        open={changePasswordOpen}
        onOpenChange={setChangePasswordOpen}
      />
      <SignOutDialog open={!!open} onOpenChange={setOpen} />
    </>
  )
}
