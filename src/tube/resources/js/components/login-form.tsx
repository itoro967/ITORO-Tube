import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Link } from '@inertiajs/react'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Checkbox } from "@/components/ui/checkbox"
import { Label } from "@/components/ui/label"
import { useForm } from '@inertiajs/react';

export function LoginForm({
  className,
  ...props
}: React.ComponentProps<"div">) {

  const { data, setData, post, errors } = useForm({
    name: '',
    password: '',
    remember_me: false,
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('auth.authenticate'));
  };

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <Card>
        <CardHeader>
          <CardTitle>Login to your account</CardTitle>
          <CardDescription>
            ログインして動画をアップロードするのじゃ！
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit}>
            <FieldGroup>
              <Field>
                <FieldLabel htmlFor="name">Name</FieldLabel>
                <Input
                  id="name"
                  type="text"
                  placeholder="例:たいまにん"
                  required
                  onChange={e => setData('name', e.target.value)}
                  value={data.name}
                />
                <FieldDescription>
                  {errors.name && <span className="text-red-500">{errors.name}</span>}
                </FieldDescription>
              </Field>
              <Field>
                <div className="flex items-center">
                  <FieldLabel htmlFor="password">Password</FieldLabel>
                </div>
                <Input id="password" type="password" required onChange={e => setData('password', e.target.value)} value={data.password} />
                <FieldDescription>
                  {errors.password && <span className="text-red-500">{errors.password}</span>}
                </FieldDescription>
              </Field>
              <div className="flex items-center gap-3">
                <Checkbox id="remember_me" checked={data.remember_me} onCheckedChange={state => typeof state === "boolean" && setData('remember_me', state)} />
                <Label htmlFor="remember_me">ログイン状態を保持する</Label>
              </div>
              <Field>
                <Button type="submit">Login</Button>
                <FieldDescription className="text-center">
                  アカウントを持っていないのじゃ？ <Link href={route('auth.create')}>サインアップ</Link>
                </FieldDescription>
              </Field>
            </FieldGroup>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
