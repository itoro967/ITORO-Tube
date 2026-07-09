<?php

namespace Tests\Feature;

use App\Jobs\VideoEncoder;
use App\Jobs\VideoThumbnail;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_publicly_viewable(): void
    {
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'a', 'video_path' => 'videos/a.mp4', 'thumbnail_path' => 't.jpg', 'encoded' => 100]);

        $this->get(route('dashboard'))->assertOk()->assertInertia(
            fn(Assert $page) => $page->component('index')
                ->has('videos', 1)
                ->where('videos.0.id', $video->id)
        );
    }

    public function test_show_excludes_current_and_unencoded_from_related(): void
    {
        $user = User::factory()->create();
        $current = $user->videos()->create(['title' => 'current', 'video_path' => 'videos/c.mp4', 'thumbnail_path' => 'c.jpg', 'encoded' => 100]);
        $done = $user->videos()->create(['title' => 'done', 'video_path' => 'videos/d.mp4', 'thumbnail_path' => 'd.jpg', 'encoded' => 100]);
        // 処理中(encoded=40)は関連一覧から除外されるべき対象。作成のみ行い、件数1=doneのみで除外を検証する
        $user->videos()->create(['title' => 'processing', 'video_path' => 'videos/p.mp4', 'thumbnail_path' => 'p.jpg', 'encoded' => 40]);

        $this->get(route('video.show', $current->id))->assertOk()->assertInertia(
            fn(Assert $page) => $page->component('video/show')
                ->has('videos', 1)
                ->where('videos.0.id', $done->id)
        );
    }

    public function test_store_requires_authentication(): void
    {
        $this->get(route('video.upload'))->assertRedirect(route('login'));
    }

    public function test_store_rejects_non_video_file(): void
    {
        Bus::fake();
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('video.store'), [
            'title' => 'bad',
            'video_file' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php'),
        ]);

        $response->assertSessionHasErrors('video_file');
        Bus::assertNothingDispatched();
    }

    public function test_store_saves_to_private_disk_and_dispatches_encoder(): void
    {
        Bus::fake();
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('video.store'), [
            'title' => 'my video',
            'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('videos', ['title' => 'my video', 'user_id' => $user->id]);
        $this->assertCount(1, Storage::disk('local')->files('tmp'));
        Bus::assertChained([VideoEncoder::class, VideoThumbnail::class]);
    }

    public function test_user_cannot_destroy_another_users_video(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $video = $owner->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'x.jpg', 'encoded' => 100]);

        $this->actingAs($other)->delete(route('video.destroy', $video->id))->assertNotFound();
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }

    public function test_owner_can_destroy_video(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $video = $owner->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'x.jpg', 'encoded' => 100]);

        $this->actingAs($owner)->delete(route('video.destroy', $video->id))->assertRedirect(route('video.manage'));
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    public function test_failed_encode_marks_video_and_keeps_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/x.jpg', 'encoded' => 50]);
        Storage::disk('local')->put('tmp/x.mp4', 'raw');
        Storage::disk('public')->put('videos/x.mp4', 'partial');

        (new VideoEncoder($video, 'tmp/x.mp4'))->failed(new \Exception('boom'));

        // レコードは残し失敗マーカー(-1)を付ける。壊れた出力と一時ファイルは削除する。
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'encoded' => Video::ENCODE_FAILED]);
        Storage::disk('local')->assertMissing('tmp/x.mp4');
        Storage::disk('public')->assertMissing('videos/x.mp4');
    }

    public function test_failed_keeps_user_supplied_thumbnail(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/custom.jpg', 'encoded' => 30]);
        Storage::disk('public')->put('thumbnails/custom.jpg', 'user-image');

        (new VideoEncoder($video, 'tmp/x.mp4'))->failed(new \Exception('boom'));

        // ユーザーが用意したサムネイルは失敗時も残す
        Storage::disk('public')->assertExists('thumbnails/custom.jpg');
    }

    public function test_failed_after_successful_encode_keeps_output(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/x.jpg', 'encoded' => 100]);
        Storage::disk('public')->put('videos/x.mp4', 'good-output');

        // エンコードは成功済み(100)。後処理エラーで failed() が走っても成果物を壊さない。
        (new VideoEncoder($video, 'tmp/x.mp4'))->failed(new \Exception('post-encode cleanup failed'));

        Storage::disk('public')->assertExists('videos/x.mp4');
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'encoded' => Video::ENCODE_COMPLETE]);
    }

    public function test_thumbnail_failure_nulls_path_when_not_generated(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        // 自動生成サムネが未生成（ファイルが存在しない）のケース
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/x.jpg', 'encoded' => 100]);

        (new VideoThumbnail($video))->failed(new \Exception('boom'));

        // 動画は再生可能なまま、サムネなしを示すため thumbnail_path は null
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'encoded' => Video::ENCODE_COMPLETE, 'thumbnail_path' => null]);
    }

    public function test_thumbnail_failure_keeps_user_replaced_thumbnail(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        // 自動サムネ待ちの状態（auto パス）でジョブを作成
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/auto.jpg', 'encoded' => 100]);
        $job = new VideoThumbnail($video);
        // リトライ中にユーザーが独自サムネへ差し替え
        $video->update(['thumbnail_path' => 'thumbnails/custom.jpg']);
        Storage::disk('public')->put('thumbnails/custom.jpg', 'user-image');

        $job->failed(new \Exception('boom'));

        // 差し替え済みなので触らない（独自サムネを残す）
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'thumbnail_path' => 'thumbnails/custom.jpg']);
        Storage::disk('public')->assertExists('thumbnails/custom.jpg');
    }

    public function test_thumbnail_generation_skipped_when_user_replaced(): void
    {
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/auto.jpg', 'encoded' => 100]);
        $job = new VideoThumbnail($video);
        // 生成前にユーザーが独自サムネへ差し替え
        $video->update(['thumbnail_path' => 'thumbnails/custom.jpg']);

        // 差し替え済みなら generateThumbnail を呼ばず（＝上書きしない）に return する
        $service = \Mockery::mock(VideoService::class);
        $service->shouldNotReceive('generateThumbnail');
        $job->handle($service);

        $this->assertDatabaseHas('videos', ['id' => $video->id, 'thumbnail_path' => 'thumbnails/custom.jpg']);
    }

    public function test_deleting_video_purges_orphaned_tmp_upload(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $video = $user->videos()->create(['title' => 'x', 'video_path' => 'videos/abc.mp4', 'thumbnail_path' => 'thumbnails/abc.jpg', 'encoded' => 10]);
        // エンコード前の生アップロードが local/tmp に残っている状態
        Storage::disk('local')->put('tmp/abc.mp4', 'raw-upload');

        $video->delete();

        // 削除時に public のファイルだけでなく local/tmp の一時ファイルも掃除する
        Storage::disk('local')->assertMissing('tmp/abc.mp4');
    }

    public function test_deleting_user_purges_video_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->videos()->create(['title' => 'x', 'video_path' => 'videos/x.mp4', 'thumbnail_path' => 'thumbnails/x.jpg', 'encoded' => 100]);
        Storage::disk('public')->put('videos/x.mp4', 'v');
        Storage::disk('public')->put('thumbnails/x.jpg', 't');

        $user->delete();

        // cascade ではなくモデル経由削除でファイルもパージされる
        Storage::disk('public')->assertMissing('videos/x.mp4');
        Storage::disk('public')->assertMissing('thumbnails/x.jpg');
        $this->assertDatabaseMissing('videos', ['video_path' => 'videos/x.mp4']);
    }
}
