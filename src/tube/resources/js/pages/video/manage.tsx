import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { VideoGrid } from '@/components/video-grid'
export default function Page({ videos }: { videos: Video[] }) {
  return (
    <MainLayout title="動画管理">
      <VideoGrid videos={videos} getHref={(video) => route('video.edit', { id: video.id })} />
    </MainLayout>
  )
}
