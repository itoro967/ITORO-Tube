import { Link } from '@inertiajs/react'
import { Video } from '@/types/video'

export function VideoCard({ video,url }: { video: Video,url:string }) {
    return (
        <Link key={video.id} className="cursor-pointer rounded-lg md:w-[30%] w-full hover:outline" href={url}>
            {(video.encoded == 100)&&
            <img src={`/storage/${video.thumbnail_path}`} className="aspect-video w-full" alt={video.title} />
            }
            {(video.encoded != 100)&&
            <div className="flex flex-col justify-between rounded-lg w-full bg-muted/80 aspect-video hover:outline">
                <div className='p-4'>エンコード処理中...</div>
                <div className="m-3 h-1 bg-gray-300 rounded">
                    <div className="h-1 bg-blue-500 rounded" style={{ width: `${video.encoded}%` }}></div>
                </div>
            </div>
            }
            <div className="bg-muted/50 rounded-b-lg p-2">
                <div className="text-lg font-semibold line-clamp-2">{video.title}</div>
                <div className="text-md text-gray-600 flex place-content-between"><span>投稿者: {video.user.name}</span><span>{new Date(video.created_at).toLocaleString()}</span></div>
            </div>
        </Link>
    );
}