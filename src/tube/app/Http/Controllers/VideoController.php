<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Jobs\VideoEncoder;
use App\Jobs\VideoThumbnail;
use Illuminate\Support\Facades\Bus;
class VideoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('word');
        $videos = Video::with('user')->when($search, fn($query) =>
            $query->where('title', 'like', "%{$search}%")
        )->latest()->get();
        return inertia('index', ['videos' => $videos]);
    }

    public function show($id)
    {
        $video = Video::with('user')->findOrFail($id);
        return inertia('video/show', ['video' => $video]);
    }

    public function upload()
    {
        return inertia('video/upload');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            // 'video_file' => 'required|file|mimes:mp4,avi,mov|max:1024000', // 最大1GB
            'video_file' => 'required|file|max:1024000', // 最大1GB
            'thumbnail_file' => 'nullable|file|mimes:jpg,jpeg,png|max:20480', // 最大20MB
        ]);


        $path_to_video = 'videos/' . uniqid() . '.mp4';
        $path_to_thumbnail = 'thumbnails/' . uniqid() . '.jpg';

        // 動画ファイルを保存
        $request->file('video_file')->storeAs('tmp', basename($path_to_video), 'public');

        $video = Auth::user()->videos()->create([
            'title' => $validated['title'],
            'video_path' => $path_to_video,
            'thumbnail_path' => $path_to_thumbnail,
        ]);
        // ジョブチェインを作成
        $chain = Bus::chain([
            new VideoEncoder($video, 'tmp/' . basename($path_to_video)),
        ]);

        if ($request->hasFile('thumbnail_file')) {
            $path_to_thumbnail = $request->file('thumbnail_file')->store('thumbnails', 'public') ;
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

    public function delete(Request $request, $id)
    {
        $video = Auth::user()->videos()->findOrFail($id);
        // 動画ファイルとサムネイルを削除
        Storage::disk('public')->delete($video->video_path);
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }
        $video->delete();
        return redirect()->route('video.manage')->with('success', '動画が削除されました。');
    }
    public function destroy(Request $request, $id)
    {
        // return inertia('video/destroy', ['id' => $id]);
    }
}
