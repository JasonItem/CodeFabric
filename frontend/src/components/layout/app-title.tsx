import { Link } from '@tanstack/react-router'
import { LucideIconByName } from '@/components/icon-picker'
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/ui/sidebar'
import { sidebarData } from './data/sidebar-data'

export function AppTitle() {
  const { setOpenMobile } = useSidebar()
  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <SidebarMenuButton
          size='lg'
          className='gap-0 py-0 hover:bg-transparent active:bg-transparent'
          asChild
        >
          <div>
            <Link
              to='/'
              onClick={() => setOpenMobile(false)}
              className='flex flex-1 items-center gap-2 text-start text-sm leading-tight'
            >
              <span className='flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground'>
                <LucideIconByName name={sidebarData.appLogoIcon} className='size-4' />
              </span>
              <span className='grid min-w-0'>
                <span className='truncate font-bold'>{sidebarData.appName}</span>
                <span className='truncate text-xs'>{sidebarData.appSubtitle}</span>
              </span>
            </Link>
          </div>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
