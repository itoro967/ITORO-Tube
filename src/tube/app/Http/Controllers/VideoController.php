<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\VideoEncoder;
use App\Jobs\VideoThumbnail;
use Illuminate\Support\Facades\Bus;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;

class VideoController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * 動画一覧を無限スクロール用の props 形状で返す。
     * simplePaginate は総件数 COUNT を発行しないため、hasMore だけ必要な用途に適する。
     */
    private function paginatedVideos(Builder $query): array
    {
        // ページング順序はここに一元化する。created_at だけでは同秒の同順位で
        // ページ跨ぎの重複/欠落が起きるため、id を一意なタイブレークに加える。
        $paginator = $query->latest()->orderBy('id', 'desc')->simplePaginate(self::PER_PAGE);

        return [
            // matchOn('id') でページ跨ぎの重複を id で解決する（オフセットずれでの重複カード防止）
            'videos' => Inertia::merge($paginator->items())->matchOn('id'),
            'pagination' => [
                'hasMore' => $paginator->hasMorePages(),
                'nextPage' => $paginator->currentPage() + 1,
            ],
        ];
    }

    public function index(Request $request)
    {
        $search = $request->input('word');
        // トップページは再生可能(エンコード完了済み)のみ表示する。
        // filled() で判定し、タイトル "0" の検索が falsy 扱いで無効化されるのを防ぐ
        $query = Video::with('user')
            ->playable()
            ->when(filled($search), fn($query) => $query->where('title', 'like', "%{$search}%"));

        return Inertia::render('index', [
            ...$this->paginatedVideos($query),
            'word' => $search,
        ]);
    }

    public function show(Request $request, $id)
    {
        // 再生可能な動画のみ公開する（処理中・失敗した動画は 404。壊れた動画/OGP を配信しない）
        $video = Video::with('user')->playable()->findOrFail($id);
        $isCrawl = preg_match('/(Line|Twitterbot|Slackbot|Discordbot)/i', (string) $request->header('User-Agent'));
        if ($isCrawl) {
            return view('showVideoOGP', ['video' => $video]);
        }

        // 関連動画も再生可能・表示中の動画以外のみ
        $query = Video::with('user')
            ->playable()
            ->whereKeyNot($video->id);

        return Inertia::render('video/show', [
            'video' => $video,
            ...$this->paginatedVideos($query),
        ]);
    }

    public function upload()
    {
        return inertia('video/upload');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'video_file' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:10240000', // 最大10GB
            'thumbnail_file' => 'nullable|file|mimes:jpg,jpeg,png|max:20480', // 最大20MB
        ]);

        $uuid = (string) Str::uuid();
        $path_to_video = 'videos/' . $uuid . '.mp4';
        $path_to_thumbnail = 'thumbnails/' . $uuid . '.jpg';

        // アップロード元の生ファイルは非公開ディスク(local)に一時保存する
        $tmpPath = $request->file('video_file')->storeAs('tmp', $uuid . '.mp4', 'local');

        $video = Auth::user()->videos()->create([
            'title' => $validated['title'],
            'video_path' => $path_to_video,
            'thumbnail_path' => $path_to_thumbnail,
        ]);
        // ジョブチェインを作成
        $chain = Bus::chain([
            new VideoEncoder($video, $tmpPath),
        ]);

        if ($request->hasFile('thumbnail_file')) {
            $path_to_thumbnail = $request->file('thumbnail_file')->store('thumbnails', 'public');
            $video->update(['thumbnail_path' => $path_to_thumbnail]);
        } else {
            $chain->append(new VideoThumbnail($video));
        }

        $chain->dispatch();

        return redirect()->route('dashboard')->with('success', '動画がアップロードされました。エンコードが完了するまでしばらくお待ちください。');
    }

    public function manage()
    {
        $videos = Auth::user()->videos()->with('user')->latest()->get();
        return inertia('video/manage', ['videos' => $videos]);
    }

    public function edit(Request $request, $id)
    {
        $video = Auth::user()->videos()->findOrFail($id);
        return inertia('video/edit', ['video' => $video]);
    }

    public function update(Request $request, $id)
    {
        $video = Auth::user()->videos()->findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|max:255',
            'thumbnail_file' => 'nullable|file|mimes:jpg,jpeg,png|max:20480', // 最大20MB
        ]);
        if ($request->hasFile('thumbnail_file')) {
            // 古いサムネイルを削除
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
            // 新しいサムネイルを保存
            $path_to_thumbnail = $request->file('thumbnail_file')->store('thumbnails', 'public');
            $video->thumbnail_path = $path_to_thumbnail;
        }
        $video->title = $validated['title'];
        $video->save();
        return redirect()->route('video.manage')->with('success', '動画情報が更新されました。');
    }

    public function destroy(Request $request, $id)
    {
        $video = Auth::user()->videos()->findOrFail($id);
        // 実ファイル削除は Video モデルの deleting イベントに集約している
        $video->delete();
        return redirect()->route('video.manage')->with('success', '動画が削除されました。');
    }
}
