<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\VideoService;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class VideoEncoder implements ShouldQueue
{
    use Queueable;

    public $timeout = 60 * 60; // 1時間

    // ジョブ滞留中に動画が削除されていたら、例外を投げず静かにジョブを破棄する
    public bool $deleteWhenMissingModels = true;

    protected string $inputFile;
    protected Video $video;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video, string $inputFile)
    {
        $this->inputFile = $inputFile;
        $this->video = $video->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(VideoService $videoService): void
    {
        // ffmpeg は出力先ディレクトリを自動生成しないため、事前に用意しておく
        Storage::disk('public')->makeDirectory(dirname($this->video->video_path));

        // 進捗更新は DB 書き込みを抑えるため 5% 刻み（および完了時）のみ反映する。
        // リトライ時に既に表示済みの進捗より下回る値を書いて逆行させないよう、現在値を起点にする。
        $lastWritten = max(0, (int) (Video::whereKey($this->video->getKey())->value('encoded') ?? 0));

        $videoService->encode(
            Storage::disk('local')->path($this->inputFile),
            Storage::disk('public')->path($this->video->video_path),
            function ($progress) use (&$lastWritten) {
                if ($progress >= Video::ENCODE_COMPLETE || $progress - $lastWritten >= 5) {
                    $lastWritten = $progress;
                    $this->video->update(['encoded' => $progress]);
                }
            }
        );

        // 変換元の一時ファイルを削除
        Storage::disk('local')->delete($this->inputFile);

        // エンコード中に動画が削除されていたら、生成した出力を後片付けする（孤立ファイル防止）
        if (!Video::whereKey($this->video->getKey())->exists()) {
            Storage::disk('public')->delete($this->video->video_path);
        }
    }

    /**
     * ジョブが最終的に失敗したときの後始末。
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('動画エンコードジョブが失敗しました: ' . ($exception?->getMessage() ?? 'unknown'));

        // 一時ファイルは常に削除
        Storage::disk('local')->delete($this->inputFile);

        $current = Video::find($this->video->getKey());
        // 既に削除済み、または後処理エラーで正常な完了成果物が残っている場合は何もしない
        if (!$current || $current->encoded === Video::ENCODE_COMPLETE) {
            return;
        }

        // 生成途中の壊れた出力のみ削除する。サムネイル（ユーザー提供の場合あり）とレコードは残し、
        // 失敗マーカーを付けて再アップロードの手掛かりにする。
        Storage::disk('public')->delete($this->video->video_path);
        $current->update(['encoded' => Video::ENCODE_FAILED]);
    }
}
