import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { VideoCard } from '@/components/video-card'
export default function Page({ videos }: { videos: Video[] }) {
  return (
    <MainLayout>
      <div className="flex gap-4 p-4 flex-wrap">
        {videos.map((video) => (
          <VideoCard key={video.id} video={video} url={route('video.show', { id: video.id })} />
        ))}
      </div>
    </MainLayout>
  )
}
