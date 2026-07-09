<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    // encoded の値: 負値=失敗 / 0〜99=処理中 / 100=完了
    public const ENCODE_FAILED = -1;
    public const ENCODE_COMPLETE = 100;

    protected $fillable = [
        'title',
        'video_path',
        'thumbnail_path',
        'encoded',
    ];
    protected $hidden = [
        'updated_at',
        'user_id',
    ];
    protected $casts = [
        'encoded' => 'integer',
    ];

    protected static function booted(): void
    {
        // 動画レコード削除時に保存済みの実ファイルも必ず消す
        static::deleting(fn(Video $video) => $video->purgeStoredFiles());
    }

    /**
     * 再生可能（エンコード完了済み）の動画に絞るスコープ。一覧・詳細で共用する。
     */
    public function scopePlayable(Builder $query): Builder
    {
        return $query->where('encoded', self::ENCODE_COMPLETE);
    }

    /**
     * 保存済みファイル（public の動画本体・サムネイル、および未処理なら local の一時ファイル）を
     * 削除する。レコード削除時に発火。エンコード前にジョブごと破棄されても一時ファイルが残らないよう、
     * video_path から一時ファイル名を復元して掃除する。
     */
    public function purgeStoredFiles(): void
    {
        if ($this->video_path) {
            Storage::disk('public')->delete($this->video_path);
            Storage::disk('local')->delete('tmp/' . basename($this->video_path));
        }
        if ($this->thumbnail_path) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
