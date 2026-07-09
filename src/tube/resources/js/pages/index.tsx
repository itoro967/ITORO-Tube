import MainLayout from '@/layouts/mainLayout'
import { Video, Pagination } from '@/types/video'
import { VideoGrid } from '@/components/video-grid'
export default function Page({ videos, pagination, word }: { videos: Video[], pagination: Pagination, word: string | null }) {
  return (
    <MainLayout>
      {/* 検索語が変わったら一覧は総入れ替えになるため、key で VideoGrid を再マウントして
          無限スクロールの内部状態（読み込み済み/エラー）をリセットする */}
      <VideoGrid key={word ?? ''} videos={videos} pagination={pagination} getHref={(video) => route('video.show', { id: video.id })} />
    </MainLayout>
  )
}
