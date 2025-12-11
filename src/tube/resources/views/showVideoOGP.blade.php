<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>{{ $video->title }}</title>
    <meta property="og:title" content="{{ $video->title }}" />
    <meta property="og:image" content="{{ asset('storage/' . $video->thumbnail_path) }}" />
    <meta property="og:description" content="投稿者: {{ $video->user->name }}" />
</head>
<body>
</body>
</html>