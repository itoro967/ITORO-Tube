<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;

class VideoThumbnail implements ShouldQueue
{
    use Queueable;
    protected Video $video;


    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(VideoService $videoService): void
    {
        $thumbnailContent = $videoService->generateThumbnail(storage_path('app/public/' . $this->video->video_path));
        Storage::disk('public')->put($this->video->thumbnail_path, $thumbnailContent);
    }
}
