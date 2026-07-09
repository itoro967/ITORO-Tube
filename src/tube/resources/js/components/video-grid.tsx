import { Video, Pagination } from '@/types/video'
import { VideoCard } from '@/components/video-card'
import { InfiniteScrollSentinel } from '@/components/infinite-scroll-sentinel'

/**
 * VideoCard のグリッド表示。pagination を渡すと末尾に無限スクロール用センチネルを付ける。
 * getHref で各カードのリンク先を差し替える（一覧=show / 管理=edit）。
 */
export function VideoGrid({ videos, getHref, pagination, className = 'flex gap-4 p-4 flex-wrap' }: {
  videos: Video[]
  getHref: (video: Video) => string
  pagination?: Pagination
  className?: string
}) {
  return (
    <>
      <div className={className}>
        {videos.map((video) => (
          <VideoCard key={video.id} video={video} url={getHref(video)} />
        ))}
      </div>
      {pagination && <InfiniteScrollSentinel pagination={pagination} />}
    </>
  )
}
