import { Button } from "@/components/ui/button"
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
import { Link } from '@inertiajs/react'

import {useForm } from '@inertiajs/react';

export function SignupForm({ ...props }: React.ComponentProps<typeof Card>) {
  const { data, setData, post, errors } = useForm({
  name: '',
  password: '',
  password_confirmation: '',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('auth.store'));
  };
  return (
    <Card {...props}>
      <CardHeader>
        <CardTitle>Create an account</CardTitle>
        <CardDescription>
          アカウントを作成して動画をアップロードするのじゃ！
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit}>
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="name">Name</FieldLabel>
              <Input id="name" type="text" placeholder="例:たいまにん" required  onChange={e => setData('name', e.target.value)} value={data.name} />
              <FieldDescription>
                {errors.name && <span className="text-red-500">{errors.name}</span>}
              </FieldDescription>
            </Field>
            <Field>
              <FieldLabel htmlFor="password">Password</FieldLabel>
              <Input id="password" type="password" required  onChange={e => setData('password', e.target.value)} value={data.password} />
              <FieldDescription>
                8文字以上で入力するのじゃ！
                {errors.password && <span className="text-red-500"><br/>{errors.password}</span>}
              </FieldDescription>
            </Field>
            <Field>
              <FieldLabel htmlFor="confirm-password">
                Confirm Password
              </FieldLabel>
              <Input id="confirm-password" type="password" required  onChange={e => setData('password_confirmation', e.target.value)} value={data.password_confirmation} />
              <FieldDescription>パスワードを確認するのじゃ！</FieldDescription>
            </Field>
            <FieldGroup>
              <Field>
                <Button type="submit">Create Account</Button>
                <FieldDescription className="px-6 text-center">
                  アカウントを持っているのじゃ？ <Link href={route('auth.login')}>サインイン</Link>
                </FieldDescription>
              </Field>
            </FieldGroup>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
  )
}
