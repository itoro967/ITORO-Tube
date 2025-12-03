import MainLayout from '@/layouts/mainLayout'
import { Video } from '@/types/video'
import { useEffect, useRef } from 'react'
export default function Page({ video }: { video: Video }) {
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
        <div>
          <div className='m-4 text-2xl'>{video.title}</div>
          <div className="ml-2 mt-1 text-md text-gray-600 flex gap-4"><span>投稿者: {video.user.name}</span><span>{new Date(video.created_at).toLocaleString()}</span></div>
        </div>
      </div>
    </MainLayout>
  )
}
