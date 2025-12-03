<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\VideoService;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
class VideoEncoder implements ShouldQueue
{
    use Queueable;

    public $timeout = 60 * 60; // 1時間

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
        $videoService->encode(
            storage_path('app/public/' . $this->inputFile),
            storage_path('app/public/' . $this->video->video_path),
            function ($progress) {
                $this->video->update(['encoded' => $progress]);
            }
        );
        unlink(storage_path('app/public/' . $this->inputFile));
    }
}
