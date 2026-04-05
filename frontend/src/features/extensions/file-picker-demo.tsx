import { useState } from 'react'
import { ImageIcon, UploadCloud, X } from 'lucide-react'
import type { StoredFile } from '@/api/file/types'
import { FilePicker } from '@/components/file-picker'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function FilePickerDemoPage() {
  const [singleImage, setSingleImage] = useState<StoredFile[]>([])
  const [images, setImages] = useState<StoredFile[]>([])
  const [singleVideoFile, setSingleVideoFile] = useState<StoredFile[]>([])
  const [singleDocFile, setSingleDocFile] = useState<StoredFile[]>([])
  const [multiFiles, setMultiFiles] = useState<StoredFile[]>([])
  const [customFiles, setCustomFiles] = useState<StoredFile[]>([])

  return (
    <Main className='space-y-4 px-4 py-5'>
      <div>
        <h1 className='text-2xl font-semibold tracking-tight'>文件选择器</h1>
        <p className='text-muted-foreground'>支持图片、视频、文件样式挂载，支持后缀限制与多选。</p>
      </div>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>图片选择（单选）</CardTitle>
          <CardDescription>单张图片上传与预览。</CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='image'
            maxCount={1}
            accept={['.jpg', '.jpeg', '.png', '.webp', '.gif']}
            value={singleImage}
            onChange={setSingleImage}
          />
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>图片选择（多选）</CardTitle>
          <CardDescription>挂载样式为图片墙，可直接预览选中图片。</CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='image'
            multiple
            maxCount={8}
            accept={['.jpg', '.jpeg', '.png', '.webp', '.gif']}
            value={images}
            onChange={setImages}
          />
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>通用文件选择（单选-视频）</CardTitle>
          <CardDescription>通过 `accept` 限制后缀，选中后支持视频预览。</CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='file'
            accept={['video/*', '.mp4', '.mov', '.webm', '.mkv', '.avi']}
            value={singleVideoFile}
            onChange={setSingleVideoFile}
          />
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>通用文件选择（单选-文档）</CardTitle>
          <CardDescription>通过 `accept` 限制仅可选文档类型。</CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='file'
            accept={['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.zip']}
            value={singleDocFile}
            onChange={setSingleDocFile}
          />
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>通用文件选择（多选）</CardTitle>
          <CardDescription>任意类型文件都支持多选，可同时选择图片、视频与文档。</CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='file'
            multiple
            maxCount={5}
            value={multiFiles}
            onChange={setMultiFiles}
          />
        </CardContent>
      </Card>

      <Card className='shadow-none'>
        <CardHeader>
          <CardTitle className='text-base'>自定义挂载（DIY）</CardTitle>
          <CardDescription>
            通过 `render` API 自定义触发按钮与预览区域，底层文件选择弹窗保持复用。
          </CardDescription>
        </CardHeader>
        <CardContent>
          <FilePicker
            variant='file'
            multiple
            maxCount={6}
            value={customFiles}
            onChange={setCustomFiles}
            render={({ selectedFiles, openPicker, removeFile, previewFile, clearFiles }) => (
              <div className='space-y-3 rounded-md border p-3'>
                <div className='flex flex-wrap items-center gap-2'>
                  <Button type='button' onClick={openPicker}>
                    <UploadCloud className='mr-2 h-4 w-4' />
                    选择系统文件
                  </Button>
                  <Button type='button' variant='outline' onClick={clearFiles}>
                    清空
                  </Button>
                  <span className='text-sm text-muted-foreground'>
                    已选择 {selectedFiles.length} 个
                  </span>
                </div>

                {selectedFiles.length === 0 ? (
                  <div className='rounded-md border border-dashed p-6 text-sm text-muted-foreground'>
                    这里是你自定义的预览区域，点击上方按钮选择文件。
                  </div>
                ) : (
                  <div className='grid grid-cols-2 gap-2 md:grid-cols-3'>
                    {selectedFiles.map((file) => (
                      <div
                        key={file.id}
                        className='flex items-center gap-2 rounded-md border p-2 text-sm'
                      >
                        <button
                          type='button'
                          className='inline-flex h-9 w-9 items-center justify-center rounded border bg-muted/20'
                          onClick={() => previewFile(file)}
                          title='预览'
                        >
                          {file.kind === 'IMAGE' ? (
                            <img src={file.url} alt={file.name} className='h-full w-full rounded object-cover' />
                          ) : (
                            <ImageIcon className='h-4 w-4 text-muted-foreground' />
                          )}
                        </button>
                        <span className='min-w-0 flex-1 truncate' title={file.name}>
                          {file.name}
                        </span>
                        <button
                          type='button'
                          className='text-muted-foreground hover:text-destructive'
                          onClick={() => removeFile(file.id)}
                        >
                          <X className='h-4 w-4' />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          />
        </CardContent>
      </Card>
    </Main>
  )
}
