<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Video;
use Throwable;

class VideoThumbnail implements ShouldQueue
{
    use Queueable;

    // ジョブ滞留中に動画が削除されていたら、例外を投げず静かにジョブを破棄する
    public bool $deleteWhenMissingModels = true;

    protected Video $video;
    // dispatch 時点の自動生成サムネのパス。SerializesModels でモデルは再取得されるため、
    // 生成後にユーザーが差し替えても上書きしないよう、生成先を素の文字列で固定しておく。
    protected string $thumbnailPath;


    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video->withoutRelations();
        $this->thumbnailPath = $video->thumbnail_path;
    }

    /**
     * Execute the job.
     */
    public function handle(VideoService $videoService): void
    {
        $current = Video::find($this->video->getKey());
        // 削除済み、またはユーザーが独自サムネに差し替え済みなら自動生成しない（上書き防止）
        if (!$current || $current->thumbnail_path !== $this->thumbnailPath) {
            return;
        }
        $thumbnailContent = $videoService->generateThumbnail(Storage::disk('public')->path($current->video_path));
        // 生成中に削除/差し替えされていたら書き込まない（孤立ファイル・上書き防止）
        if (!Video::whereKey($this->video->getKey())->where('thumbnail_path', $this->thumbnailPath)->exists()) {
            return;
        }
        Storage::disk('public')->put($this->thumbnailPath, $thumbnailContent);
    }

    /**
     * サムネイル生成が最終的に失敗したとき。動画自体は再生可能(encoded=100)なので、
     * 自動生成の途中生成物（部分書き込み含む）を消して thumbnail_path を null にし「サムネなし」を示す。
     * リトライ中にユーザーが差し替えた独自サムネ（生成先と異なるパス）は一切触らない。
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('サムネイル生成ジョブが失敗しました: ' . ($exception?->getMessage() ?? 'unknown'));
        $current = Video::find($this->video->getKey());
        if (!$current || $current->thumbnail_path !== $this->thumbnailPath) {
            return;
        }
        Storage::disk('public')->delete($this->thumbnailPath);
        $current->update(['thumbnail_path' => null]);
    }
}
