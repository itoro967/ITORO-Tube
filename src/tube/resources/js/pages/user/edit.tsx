import MainLayout from '@/layouts/mainLayout'
import { Auth } from '@/types/auth';

import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field"
import { TbCameraPlus } from "react-icons/tb";
import { Input } from "@/components/ui/input"
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { Link } from '@inertiajs/react';
export default function Page({ user }: { user:Auth }) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [profileImage, setProfileImage] = useState<string | null>(`/storage/${user?.profile_image_path}` || null);
  const {data, setData,post,errors} = useForm({
    name: user?.name,
    profile_image: user?.profile_image,
  });
    const submit = (e: React.FormEvent) => {
      e.preventDefault();
      post(route('user.update'));
  };

  const handleFileChange =(e: React.ChangeEvent<HTMLInputElement>)=>{
    if(e.target.files?.length){
      const file = e.target.files[0];
      const reader = new FileReader();
      setData('profile_image', file);
      reader.onload = (event) => {
        setProfileImage(event.target?.result as string);
      };
      reader.readAsDataURL(file);
    }
  }

  return (
    <MainLayout title="アカウント管理">
      <Button asChild variant="default" className="ml-4 w-20 -mt-12 ml-auto mr-5">
        <Link href={route('auth.logout')}>Logout</Link>
      </Button>
      <form onSubmit={submit}>
      <FieldGroup className='w-1/2 mx-auto mt-10'>
        <div className='cursor-pointer bg-white/40 size-30 bg-cover bg-center rounded-full flex justify-center items-center mx-auto'
          style={profileImage ? { backgroundImage: `url(${profileImage})` } : {}}
          onClick={() => {fileInputRef.current?.click()}}
        >
          <TbCameraPlus className='size-10 bg-muted/50 rounded-md p-1'/>
        </div>
        <input type="file" ref={fileInputRef} onChange={handleFileChange} hidden />
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
