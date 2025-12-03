import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { Link } from '@inertiajs/react'
import { Auth } from '@/types/auth';
export default function Page({ user }: { user: Auth }) {
  return (
    <MainLayout title="アカウント管理">
      <div className="p-4">
        必死に実装中
      </div>
    </MainLayout>
  )
}
