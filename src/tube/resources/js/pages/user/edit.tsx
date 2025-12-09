import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { Link } from '@inertiajs/react'
import { Auth } from '@/types/auth';

import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
export default function Page({ user }: { user:Auth }) {
  const {data, setData,post,errors} = useForm({
    name: user?.name,
  });
    const submit = (e: React.FormEvent) => {
      e.preventDefault();
      post(route('user.update'));
  };

  return (
    <MainLayout title="アカウント管理">
      <form onSubmit={submit}>
      <FieldGroup className='w-1/2 mx-auto mt-10'>
        <Field>
          <FieldLabel htmlFor="name" >名前</FieldLabel>
          <Input id="name" defaultValue={data.name} onChange={e => setData('name', e.target.value)} required />
          <FieldDescription>
            {errors.name && <span className="text-red-500">{errors.name}</span>}
          </FieldDescription>
        </Field>
        <Button className='mt-5 cursor-pointer'>更新</Button>
      </FieldGroup>
      </form>
    </MainLayout>
  )
}
