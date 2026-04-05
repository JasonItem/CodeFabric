import { CalendarClock, CircleHelp, Triangle } from 'lucide-react'
import { type ReactNode } from 'react'
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { type NameType, type ValueType } from 'recharts/types/component/DefaultTooltipContent'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'

const colorPrimary = 'var(--primary)'
const colorMutedForeground = 'var(--muted-foreground)'
const colorBorder = 'var(--border)'

const monthlySalesData = [
  { month: '1月', sales: 810, visits: 1260 },
  { month: '2月', sales: 540, visits: 980 },
  { month: '3月', sales: 920, visits: 1380 },
  { month: '4月', sales: 780, visits: 1210 },
  { month: '5月', sales: 360, visits: 690 },
  { month: '6月', sales: 800, visits: 1250 },
  { month: '7月', sales: 720, visits: 1120 },
  { month: '8月', sales: 1200, visits: 1610 },
  { month: '9月', sales: 1060, visits: 1490 },
  { month: '10月', sales: 280, visits: 510 },
  { month: '11月', sales: 390, visits: 630 },
  { month: '12月', sales: 1100, visits: 1540 },
]

const visitSparkData = [
  { t: '1', v: 320 },
  { t: '2', v: 280 },
  { t: '3', v: 260 },
  { t: '4', v: 310 },
  { t: '5', v: 290 },
  { t: '6', v: 300 },
  { t: '7', v: 275 },
  { t: '8', v: 360 },
  { t: '9', v: 330 },
  { t: '10', v: 240 },
  { t: '11', v: 220 },
  { t: '12', v: 305 },
]

const paySparkData = [
  { t: '1', v: 32 },
  { t: '2', v: 25 },
  { t: '3', v: 27 },
  { t: '4', v: 18 },
  { t: '5', v: 15 },
  { t: '6', v: 26 },
  { t: '7', v: 33 },
  { t: '8', v: 29 },
  { t: '9', v: 30 },
  { t: '10', v: 28 },
  { t: '11', v: 36 },
  { t: '12', v: 31 },
]

const hourData = [
  { time: '00:00', pv: 98, uv: 65 },
  { time: '00:05', pv: 160, uv: 88 },
  { time: '00:10', pv: 240, uv: 128 },
  { time: '00:15', pv: 390, uv: 220 },
  { time: '00:20', pv: 405, uv: 238 },
  { time: '00:25', pv: 280, uv: 170 },
  { time: '00:30', pv: 160, uv: 110 },
  { time: '00:35', pv: 220, uv: 146 },
  { time: '00:40', pv: 330, uv: 188 },
  { time: '00:45', pv: 360, uv: 210 },
  { time: '00:50', pv: 300, uv: 178 },
  { time: '00:55', pv: 268, uv: 150 },
]

const rankData = [
  { name: '工专路 1 号店', amount: 333001 },
  { name: '工专路 2 号店', amount: 333002 },
  { name: '工专路 3 号店', amount: 333003 },
  { name: '工专路 4 号店', amount: 333004 },
  { name: '工专路 5 号店', amount: 333005 },
  { name: '工专路 6 号店', amount: 333006 },
  { name: '工专路 7 号店', amount: 333007 },
]

const hotWords = [
  '私域运营',
  '自动化工作流',
  '大模型集成',
  '企业知识库',
  '低代码平台',
  '权限管理',
  '用户留存',
  '漏斗分析',
  '系统监控',
  '角色分配',
  '菜单配置',
  '字典管理',
  '消息中心',
  '多租户',
  '日志审计',
  '接口鉴权',
]

function ChartTooltipContent({
  active,
  label,
  payload,
  formatter,
}: {
  active?: boolean
  label?: ReactNode
  payload?: Array<{ name?: NameType; value?: ValueType; color?: string }>
  formatter?: (name: string) => string
}) {
  if (!active || !payload || payload.length === 0) return null

  return (
    <div className='min-w-32 rounded-md border bg-popover px-3 py-2 text-sm text-popover-foreground shadow-md'>
      {label ? <div className='mb-1.5 font-medium'>{label}</div> : null}
      <div className='space-y-1'>
        {payload.map((entry, index) => {
          const key = String(entry.name ?? index)
          const labelText = formatter ? formatter(key) : key
          return (
            <div key={key} className='flex items-center gap-2'>
              <span
                className='inline-block h-2 w-2 rounded-full'
                style={{ backgroundColor: entry.color || 'currentColor' }}
              />
              <span className='text-muted-foreground'>{labelText}</span>
              <span className='ms-auto font-medium text-foreground'>{entry.value}</span>
            </div>
          )
        })}
      </div>
    </div>
  )
}

function MetricCard({
  title,
  value,
  tag,
  footer,
  children,
}: {
  title: string
  value: string
  tag?: string
  footer: React.ReactNode
  children?: React.ReactNode
}) {
  return (
    <Card className='h-full gap-2 py-3 shadow-none'>
      <CardHeader className='px-5 pb-0'>
        <div className='flex items-center justify-between'>
          <CardTitle className='text-sm text-muted-foreground'>{title}</CardTitle>
          {tag ? <Badge variant='outline'>{tag}</Badge> : <CircleHelp className='h-4 w-4 text-muted-foreground' />}
        </div>
      </CardHeader>
      <CardContent className='flex flex-1 flex-col gap-1.5 px-5'>
        <div className='text-2xl leading-none font-semibold tracking-tight'>{value}</div>
        {children}
        <div className='mt-auto border-t pt-3 text-xs'>{footer}</div>
      </CardContent>
    </Card>
  )
}

export function Dashboard() {
  return (
    <Main className='space-y-4 px-4 py-5'>
      <div className='grid auto-rows-fr gap-4 sm:grid-cols-2 xl:grid-cols-4'>
        <MetricCard
          title='总销售额'
          value='¥ 126,560'
          footer='日销售额 ¥ 12,423'
        >
          <div className='flex min-h-[40px] items-end gap-6 text-sm'>
            <span className='inline-flex items-center gap-1 text-muted-foreground'>
              同比12%
              <Triangle className='h-2.5 w-2.5 fill-green-500 text-green-500' />
            </span>
            <span className='inline-flex items-center gap-1 text-muted-foreground'>
              日同比11%
              <Triangle className='h-2.5 w-2.5 rotate-180 fill-red-500 text-red-500' />
            </span>
          </div>
        </MetricCard>

        <MetricCard title='访问量' value='8,846' tag='日' footer='日访问量 1,234'>
          <div className='h-[42px]'>
            <ResponsiveContainer width='100%' height='100%'>
              <AreaChart data={visitSparkData}>
                <Area type='monotone' dataKey='v' stroke={colorPrimary} fill={colorPrimary} fillOpacity={0.12} strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </MetricCard>

        <MetricCard title='支付笔数' value='6,560' tag='月' footer='转化率 60%'>
          <div className='h-[42px]'>
            <ResponsiveContainer width='100%' height='100%'>
              <BarChart data={paySparkData}>
                <Bar dataKey='v' fill={colorPrimary} radius={[2, 2, 2, 2]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </MetricCard>

        <MetricCard
          title='活动运营效果'
          value='78%'
          tag='周'
          footer={
            <div className='flex items-center gap-6 text-sm'>
              <span className='inline-flex items-center gap-1 text-muted-foreground'>
                同比12%
                <Triangle className='h-2.5 w-2.5 fill-green-500 text-green-500' />
              </span>
              <span className='inline-flex items-center gap-1 text-muted-foreground'>
                日同比11%
                <Triangle className='h-2.5 w-2.5 rotate-180 fill-red-500 text-red-500' />
              </span>
            </div>
          }
        >
          <div className='flex min-h-[42px] items-center'>
            <div className='h-2.5 w-full rounded-full bg-muted'>
            <div className='h-2.5 w-[78%] rounded-full bg-primary' />
            </div>
          </div>
        </MetricCard>
      </div>

      <Card className='shadow-none'>
        <CardHeader className='flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between'>
          <Tabs defaultValue='sales'>
            <TabsList>
              <TabsTrigger value='sales'>销售额</TabsTrigger>
              <TabsTrigger value='visits'>访问量</TabsTrigger>
            </TabsList>
          </Tabs>

          <div className='flex flex-wrap items-center gap-2'>
            <div className='flex items-center gap-1'>
              <Button variant='outline' size='sm'>今天</Button>
              <Button size='sm'>本周</Button>
              <Button variant='outline' size='sm'>本月</Button>
              <Button variant='outline' size='sm'>本年</Button>
            </div>
            <Button variant='outline' size='sm' className='gap-2'>
              <CalendarClock className='h-4 w-4' />
              2026-03-16 00:00 - 2026-03-22 23:59
            </Button>
          </div>
        </CardHeader>

        <CardContent className='grid gap-4 pt-4 lg:grid-cols-[3fr_1.2fr]'>
          <div>
            <div className='mb-3 text-sm font-medium'>销售量趋势</div>
            <div className='h-[360px]'>
              <ResponsiveContainer width='100%' height='100%'>
                <BarChart data={monthlySalesData}>
                  <CartesianGrid stroke={colorBorder} strokeDasharray='3 3' vertical={false} />
                  <XAxis dataKey='month' tickLine={false} axisLine={false} />
                  <YAxis tickLine={false} axisLine={false} />
                  <Tooltip
                    content={<ChartTooltipContent formatter={() => '销售额'} />}
                    cursor={{ fill: 'var(--muted)', opacity: 0.25 }}
                  />
                  <Bar dataKey='sales' fill={colorPrimary} radius={[8, 8, 0, 0]} barSize={18} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>

          <div>
            <div className='mb-3 text-sm font-medium'>门店销售额排名</div>
            <ul className='space-y-2'>
              {rankData.map((item, index) => (
                <li key={item.name} className='flex items-center gap-3 rounded-md border px-3 py-2'>
                  <span className='inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-semibold'>
                    {index + 1}
                  </span>
                  <span className='flex-1 text-sm'>{item.name}</span>
                  <span className='text-sm text-muted-foreground'>{item.amount.toLocaleString()}</span>
                </li>
              ))}
            </ul>
          </div>
        </CardContent>
      </Card>

      <div className='grid gap-4 lg:grid-cols-[2fr_1fr]'>
        <Card className='shadow-none'>
          <CardHeader className='border-b'>
            <CardTitle className='text-base'>最近1小时访问情况</CardTitle>
          </CardHeader>
          <CardContent className='pt-4'>
            <div className='h-[300px]'>
              <ResponsiveContainer width='100%' height='100%'>
                <AreaChart data={hourData}>
                  <CartesianGrid stroke={colorBorder} strokeDasharray='3 3' vertical={false} />
                  <XAxis dataKey='time' tickLine={false} axisLine={false} tick={{ fill: colorMutedForeground }} />
                  <YAxis tickLine={false} axisLine={false} tick={{ fill: colorMutedForeground }} />
                  <Tooltip
                    content={<ChartTooltipContent formatter={(name) => (name === 'pv' ? '浏览量' : '访问量')} />}
                    cursor={{ stroke: colorBorder }}
                  />
                  <Area
                    type='monotone'
                    dataKey='pv'
                    name='浏览量'
                    stroke={colorPrimary}
                    fill={colorPrimary}
                    fillOpacity={0.14}
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4, fill: colorPrimary }}
                  />
                  <Area
                    type='monotone'
                    dataKey='uv'
                    name='访问量'
                    stroke={colorMutedForeground}
                    fill={colorMutedForeground}
                    fillOpacity={0.08}
                    strokeWidth={1.75}
                    dot={false}
                    activeDot={{ r: 4, fill: colorMutedForeground }}
                  />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        <Card className='shadow-none'>
          <CardHeader className='border-b'>
            <CardTitle className='text-base'>热门搜索</CardTitle>
          </CardHeader>
          <CardContent className='pt-4'>
            <div className='flex min-h-[300px] flex-wrap content-start gap-2'>
              {hotWords.map((word) => (
                <Badge key={word} variant='secondary' className='text-sm font-normal'>
                  {word}
                </Badge>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </Main>
  )
}
