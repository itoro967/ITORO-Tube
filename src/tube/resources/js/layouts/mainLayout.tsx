import { AppSidebar } from "@/components/app-sidebar"
import { Separator } from "@/components/ui/separator"
import { SearchForm } from "@/components/search-form"
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import { CheckCircle2Icon } from "lucide-react"
import { Alert,AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Link } from '@inertiajs/react'

import { usePage } from '@inertiajs/react'
import { Auth_page } from '@/types/auth';
import { useEffect,useState } from "react";
export default function MainLayout({ title=null,children }: { title?: string | null, children: React.ReactNode }) {
  const { name,auth, flash } = usePage<{ name: string, auth: Auth_page, flash: { success: string | null, error: string | null } }>().props;
  const [localFlash, setLocalFlash] = useState<string | null>(flash.success);

  useEffect(() => {
    if (flash.success) {
      setLocalFlash(flash.success);
      const timeout = setTimeout(() => {
        setLocalFlash(null);
        console.log('Flash message cleared');
      }, 3000);

      // クリーンアップ関数でタイマーをクリア
      return () => clearTimeout(timeout);
    }
  }, [flash.success]);

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4 fixed w-full bg-background z-10">
          <SidebarTrigger className="-ml-1" />
          <Separator
            orientation="vertical"
            className=" data-[orientation=vertical]:h-4"
          />
          {name}
          <SearchForm />

          {auth?.user && auth.user.name}
          {!auth?.user && (
            <Button asChild variant="default" className="ml-4">
              <Link href={route('auth.login')}>Login</Link>
            </Button>
          )}
        </header>
        <div className="mt-16"/>
        {localFlash && (
        <Alert className="m-1 w-2/3 bg-green-700">
          <CheckCircle2Icon />
          <AlertTitle>{localFlash}</AlertTitle>
        </Alert>
        )}
        {title && (
        <div className="text-xl font-bold p-4">{title}</div>
        )}
        {children}
      </SidebarInset>
    </SidebarProvider>
  )
}
