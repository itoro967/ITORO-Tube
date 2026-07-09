import MainLayout from '@/layouts/mainLayout'
import { Video, Pagination } from '@/types/video'
import { VideoGrid } from '@/components/video-grid'
import { useEffect, useRef } from 'react'
import { Head } from '@inertiajs/react'
export default function Page({ video, videos, pagination }: { video: Video, videos: Video[], pagination: Pagination }) {
  // jsで自動再生させる
  const videoRef = useRef<HTMLVideoElement>(null)
  useEffect(() => {
    videoRef.current?.play()
  }, [])

  return (
    <MainLayout>
      <Head>
        <title>{video.title}</title>
      </Head>
      <div className="h-full p-4">
        <video
          key={video.id}
          ref={videoRef}
          controls
          className="aspect-video max-h-[70vh] w-full bg-black"
          src={`/storage/${video.video_path}`}
          poster={video.thumbnail_path ? `/storage/${video.thumbnail_path}` : undefined}
        />
        <div className='flex items-center gap-2'>
          <div className="size-15 rounded-full bg-cover bg-center flex-none" style={video.user.profile_image_path ? { backgroundImage: `url(/storage/${video.user.profile_image_path})` } : {}}/>
          <div className='w-full'>
            <div className='text-lg line-clamp-2'>{video.title}</div>
            <div className="ml-2 mt-1 text-md text-gray-600 flex gap-4"><span>投稿者: {video.user.name}</span><span>{new Date(video.created_at).toLocaleString()}</span></div>
          </div>
        </div>

        <VideoGrid
          videos={videos}
          pagination={pagination}
          getHref={(video) => route('video.show', { id: video.id })}
          className="flex gap-4 flex-wrap mt-20"
        />
      </div>
    </MainLayout>
  )
}
