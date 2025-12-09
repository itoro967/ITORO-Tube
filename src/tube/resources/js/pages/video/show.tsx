import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { VideoCard } from '@/components/video-card'
import { useEffect, useRef } from 'react'
export default function Page({ video, videos }: { video: Video, videos: Video[] }) {
  // jsで自動再生させる
  const videoRef = useRef<HTMLVideoElement>(null)
  useEffect(() => {
    videoRef.current?.play()
  }, [])

  return (
    <MainLayout>
      <div className="h-full p-4">
        <video
          key={video.id}
          ref={videoRef}
          controls
          className="aspect-video max-h-[70vh] w-full bg-black"
          src={`/storage/${video.video_path}`}
          poster={`/storage/${video.thumbnail_path}`}
        />
        <div className='flex items-center gap-2'>
          <div className="size-15 rounded-full bg-cover bg-center flex-none" style={video.user.profile_image_path ? { backgroundImage: `url(/storage/${video.user.profile_image_path})` } : {}}/>
          <div className='w-full'>
            <div className='text-lg line-clamp-2'>{video.title}</div>
            <div className="ml-2 mt-1 text-md text-gray-600 flex gap-4"><span>投稿者: {video.user.name}</span><span>{new Date(video.created_at).toLocaleString()}</span></div>
          </div>
        </div>

        <div className="flex gap-4 flex-wrap mt-20">
          {videos.map((video) => (
            <VideoCard key={video.id} video={video} url={route('video.show', { id: video.id })} />
          ))}
        </div>
      </div>
    </MainLayout>
  )
}
