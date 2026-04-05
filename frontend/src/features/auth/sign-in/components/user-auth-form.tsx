import { useState } from "react"
import { z } from "zod"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2, LogIn } from "lucide-react"
import { toast } from "sonner"
import { authApi } from "@/api/auth"
import { useAuthStore } from "@/stores/auth-store"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { PasswordInput } from "@/components/password-input"

const formSchema = z.object({
  username: z
    .string()
    .min(1, "请输入账号")
    .max(50, "账号长度不能超过 50"),
  password: z
    .string()
    .min(1, "请输入密码")
    .min(6, "密码至少 6 位"),
})

interface UserAuthFormProps extends React.HTMLAttributes<HTMLFormElement> {
  redirectTo?: string
}

function normalizeAppPath(inputPath: string) {
  const noAdmin = inputPath.startsWith('/admin')
    ? inputPath.replace(/^\/admin/, '')
    : inputPath
  const normalized = noAdmin.startsWith('/') ? noAdmin : `/${noAdmin}`
  return normalized || '/'
}

function normalizeRedirectTarget(redirectTo?: string) {
  if (!redirectTo) return '/'

  const trimmed = redirectTo.trim()
  if (!trimmed) return '/'

  // 兼容历史参数里可能携带的完整 URL，避免 navigate({ to }) 触发 404
  if (/^https?:\/\//i.test(trimmed)) {
    try {
      const url = new URL(trimmed)
      const path = normalizeAppPath(url.pathname)
      const normalized = `${path}${url.search}${url.hash}`
      if (!normalized.startsWith('/sign-in')) return normalized || '/'
    } catch {
      return '/'
    }
    return '/'
  }

  if (!trimmed.startsWith('/')) return '/'
  const normalized = normalizeAppPath(trimmed)
  if (normalized.startsWith('/sign-in')) return '/'
  return normalized
}

export function UserAuthForm({
  className,
  redirectTo,
  ...props
}: UserAuthFormProps) {
  const [isLoading, setIsLoading] = useState(false)
  const { auth } = useAuthStore()

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      username: "admin",
      password: "admin123",
    },
  })

  async function onSubmit(data: z.infer<typeof formSchema>) {
    setIsLoading(true)

    try {
      const bundle = await authApi.login({
        username: data.username,
        password: data.password,
      })

      auth.setSession(bundle)
      auth.setAccessToken("logged-in")

      const targetPath = normalizeRedirectTarget(redirectTo)
      // 用浏览器级跳转兜底，避免路由 to 参数匹配差异导致 404
      window.location.replace(targetPath)
      toast.success(`欢迎回来，${bundle.user.nickname}`)
    } catch (error) {
      const message = error instanceof Error ? error.message : "登录失败"
      toast.error(message)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn("grid gap-3", className)}
        {...props}
      >
        <FormField
          control={form.control}
          name="username"
          render={({ field }) => (
            <FormItem>
              <FormLabel>账号</FormLabel>
              <FormControl>
                <Input placeholder="admin" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem className="relative">
              <FormLabel>密码</FormLabel>
              <FormControl>
                <PasswordInput placeholder="********" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button className="mt-2" disabled={isLoading}>
          {isLoading ? <Loader2 className="animate-spin" /> : <LogIn />}
          登录
        </Button>
      </form>
    </Form>
  )
}
