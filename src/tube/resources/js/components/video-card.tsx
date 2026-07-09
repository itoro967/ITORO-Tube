import { Link } from '@inertiajs/react'
import { useState } from 'react'
import { Video } from '@/types/video'

export function VideoCard({ video,url }: { video: Video,url:string }) {
    // 生成前(一時的な404)や失敗(null)でサムネが無いときはプレースホルダにフォールバックする
    const [thumbFailed, setThumbFailed] = useState(false)
    return (
        <Link className="cursor-pointer rounded-lg md:w-[30%] w-full hover:outline h-fit" href={url}>
            {(video.encoded === 100) && (video.thumbnail_path && !thumbFailed
                ? <img src={`/storage/${video.thumbnail_path}`} onError={() => setThumbFailed(true)} className="aspect-video w-full rounded-t-lg" alt={video.title} />
                : <div className="flex items-center justify-center rounded-t-lg w-full bg-muted/80 aspect-video text-gray-500">サムネイルなし</div>
            )}
            {(video.encoded < 0)&&
            <div className="flex items-center justify-center rounded-t-lg w-full bg-muted/80 aspect-video text-red-500">
                エンコードに失敗しました
            </div>
            }
            {(video.encoded >= 0 && video.encoded !== 100)&&
            <div className="flex flex-col justify-between rounded-t-lg w-full bg-muted/80 aspect-video hover:outline">
                <div className='p-4'>エンコード処理中...</div>
                <div className="m-3 h-1 bg-gray-300 rounded">
                    <div className="h-1 bg-blue-500 rounded" style={{ width: `${video.encoded}%` }}></div>
                </div>
            </div>
            }
            <div className="bg-muted/50 rounded-b-lg p-2 flex items-center gap-2">
                <div className="size-15 rounded-full bg-cover bg-center flex-none" style={video.user.profile_image_path ? { backgroundImage: `url(/storage/${video.user.profile_image_path})` } : {}}/>
                <div className='w-full'>
                    <div className="text-lg font-semibold line-clamp-2">{video.title}</div>
                    <div className="text-md text-gray-600 flex place-content-between"><span>{video.user.name}</span><span>{new Date(video.created_at).toLocaleString()}</span></div>
                </div>
            </div>
        </Link>
    );
}