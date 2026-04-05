import { appConfig } from '@/config/app-config'
import { LucideIconByName } from '@/components/icon-picker'

type AuthLayoutProps = {
  children: React.ReactNode
}

export function AuthLayout({ children }: AuthLayoutProps) {
  return (
    <div className='container grid h-svh max-w-none items-center justify-center'>
      <div className='mx-auto flex w-full flex-col justify-center space-y-2 py-8 sm:w-[480px] sm:p-8'>
        <div className='mb-4 flex items-center justify-center'>
          <span className='me-2 flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground'>
            <LucideIconByName name={appConfig.appLogoIcon} className='size-4' />
          </span>
          <h1 className='text-xl font-medium'>{appConfig.appName}</h1>
        </div>
        {children}
      </div>
    </div>
  )
}
