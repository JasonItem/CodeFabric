import { useState } from 'react'
import { PhotoSlider, PhotoProvider, PhotoView } from 'react-photo-view'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Main } from '@/components/layout/main'

export default function MediaPreviewDemoPage() {
  const images = [
    '/avatars/shadcn.jpg',
    'https://images.unsplash.com/photo-1469474968028-56623f02e42e',
    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
  ]

  const [visible, setVisible] = useState(false)
  const [index, setIndex] = useState(0)

  return (
    <Main className='space-y-4 px-4 py-5'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>图片预览组件</h1>
        <p className='text-muted-foreground'>基于 react-photo-view，支持缩略图预览与多图切换。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>基础示例</CardTitle>
          <CardDescription>点击缩略图可全屏预览，支持上一张/下一张切换。</CardDescription>
        </CardHeader>
        <CardContent className='space-y-3'>
          <PhotoProvider>
            <div className='flex flex-wrap gap-2'>
              {images.map((src) => (
                <PhotoView key={src} src={src}>
                  <img
                    src={src}
                    alt='示例图片'
                    className='h-24 w-24 cursor-zoom-in rounded-md border object-cover'
                  />
                </PhotoView>
              ))}
            </div>
          </PhotoProvider>
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>程序化打开示例</CardTitle>
          <CardDescription>可在业务中通过按钮直接打开指定下标的图片。</CardDescription>
        </CardHeader>
        <CardContent className='flex flex-wrap gap-2'>
          <Button
            onClick={() => {
              setIndex(0)
              setVisible(true)
            }}
          >
            打开第 1 张
          </Button>
          <Button
            variant='outline'
            onClick={() => {
              setIndex(2)
              setVisible(true)
            }}
          >
            打开第 3 张
          </Button>
        </CardContent>
      </Card>

      <PhotoSlider
        images={images.map((src, i) => ({ key: String(i), src }))}
        visible={visible}
        index={index}
        onIndexChange={setIndex}
        onClose={() => setVisible(false)}
      />
    </Main>
  )
}
