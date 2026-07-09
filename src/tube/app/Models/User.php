<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        // ユーザー削除時、DB の外部キー cascade は Eloquent の deleting を発火させず
        // 動画ファイルが孤立するため、先に各 Video をモデル経由で削除してファイルも消す。
        // lazyById() は id 範囲で再クエリするため、反復中に行を削除しても取りこぼさず（cursor と違い
        // ストリーミング結果を壊さない）、メモリも節約できる。ファイル削除は元に戻せないため、
        // 敢えてトランザクションで囲まない（ロールバックすると行だけ復活しファイルが消えた不整合になる）。
        static::deleting(function (User $user) {
            $user->videos()->lazyById()->each(fn(Video $video) => $video->delete());
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'password',
        'remember_token',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
