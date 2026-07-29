import { router } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'
import { Pagination } from '@/types/video'

const DEFAULT_ONLY = ['videos', 'pagination']

/**
 * リストの末尾に置くと、可視になった時点で Inertia の部分リロード（Inertia::merge）で次ページを
 * 追加読み込みする。WhenVisible の暗黙挙動に依存せず、自前の IntersectionObserver で
 * 「可視の間は連続読み込み」「失敗したら停止して再試行UIを出す」を明示制御する。
 *
 * 読み込むたびに URL の ?page=N を更新する（履歴は replace なので増えない）。これにより
 * 追加読み込み後の一覧が history state に保存され、動画から戻ったときに全件＋スクロール位置が
 * 復元される。リロード等で history state が失われた場合はサーバー側が ?page=N から
 * 1〜N ページ目をまとめて返して復元する。
 *
 * 検索などで一覧が置き換わる場合は、呼び出し側で `key` を変えて本コンポーネントを再マウントし、
 * 内部状態（errored ページ等）をリセットすること。
 */
export function InfiniteScrollSentinel({ pagination, only = DEFAULT_ONLY }: { pagination: Pagination, only?: string[] }) {
  const sentinelRef = useRef<HTMLDivElement | null>(null)
  const [visible, setVisible] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(false)
  // 失敗したページ番号。同じページの無駄な再試行を防ぐ（サーバー連打防止）
  const erroredPageRef = useRef<number | null>(null)

  // センチネルの可視状態を監視する（要素は常にあるので observer は一度だけ張る）
  useEffect(() => {
    const el = sentinelRef.current
    if (!el) return
    const observer = new IntersectionObserver(([entry]) => setVisible(entry.isIntersecting))
    observer.observe(el)
    return () => observer.disconnect()
  }, [])

  // 可視かつ未読み込み・未エラーなら次ページを取得。成功で nextPage が進むと本 effect が再評価され、
  // 可視のままなら連続して次を読む（短い一覧でも最後まで到達できる）。
  useEffect(() => {
    if (!visible || loading || error || !pagination.hasMore) return
    if (erroredPageRef.current === pagination.nextPage) return

    const requestedPage = pagination.nextPage
    let succeeded = false
    setLoading(true)
    router.reload({
      only,
      data: { page: requestedPage },
      // preserveUrl を付けると Inertia が history へ page を書き戻さなくなり
      // (History.pushState/replaceState が preserveUrl で早期 return する)、
      // 戻る操作で「1ページ目だけ」の状態に復元されてスクロール位置も失われる。
      // replace で URL に ?page=N を反映しつつ、履歴エントリは1件のまま保つ。
      replace: true,
      onSuccess: () => { succeeded = true },
      onFinish: () => {
        setLoading(false)
        // onError は 422 でしか発火しないため、成功しなかった＝失敗として扱い再試行を止める
        if (!succeeded) {
          erroredPageRef.current = requestedPage
          setError(true)
        }
      },
    })
  }, [visible, loading, error, pagination.hasMore, pagination.nextPage, only])

  if (!pagination.hasMore && !loading && !error) return null

  return (
    <div ref={sentinelRef} className="p-4 text-center text-gray-500">
      {loading && '読み込み中...'}
      {error && !loading && (
        <button
          type="button"
          className="underline"
          onClick={() => { erroredPageRef.current = null; setError(false) }}
        >
          読み込みに失敗しました。再試行
        </button>
      )}
    </div>
  )
}
