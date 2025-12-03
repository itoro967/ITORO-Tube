<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
