<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

class VideoService
{
    static function generateThumbnail($videoPath)
    {
        // 一時ファイルを作成
        $tempFile = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';

        // FFmpegコマンドを使用して動画の最初のフレームをサムネイルとして保存する
        $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -ss 00:00:00.100 -frames:v 1 " . escapeshellarg($tempFile);

        exec($command, $output, $returnVar);

        // FFmpegの実行結果を確認
        if ($returnVar !== 0) {
            Log::error("FFmpeg failed to generate thumbnail. Command: $command, Return Code: $returnVar, Output: " . implode("\n", $output));
            throw new \Exception("Failed to generate thumbnail using FFmpeg.");
        }
        // サムネイルをメモリに読み込む
        if (!file_exists($tempFile)) {
            throw new \Exception("Temporary thumbnail file was not created: $tempFile");
        }
        $thumbnailContent = file_get_contents($tempFile);
        // 一時ファイルを削除
        unlink($tempFile);
        // サムネイルのバイナリデータを返す
        return $thumbnailContent;
    }

    static function encode($inputFile, $outputFile, $progressCallback = null){
        try {
            // 入力動画のフレーム数を取得する
            $totalFrame = 0;
            $ffprobeCommand = "ffprobe -v error -select_streams v:0 -count_frames -show_entries stream=nb_read_frames -of default=nokey=1:noprint_wrappers=1 " . escapeshellarg($inputFile);
            $output = [];
            exec($ffprobeCommand, $output, $returnVar);
            if ($returnVar === 0 && isset($output[0])) {
                $totalFrame = (int)$output[0];
            } else {
                throw new \Exception("動画の総フレーム数の取得に失敗しました。");
            }

            // FFmpegを使用して動画をエンコード
            $command = "ffmpeg -i {$inputFile} -vcodec libx264 -crf 23 {$outputFile}". " -progress pipe:2";
            // プロセスを開始
            $descriptors = [
                1 => ['pipe', 'w'], // 標準出力
                2 => ['pipe', 'w'], // 標準エラー出力
            ];
            $process = proc_open($command, $descriptors, $pipes);

            if (!is_resource($process)) {
                throw new \Exception("FFmpegプロセスの開始に失敗しました。");
            }

            // 標準エラー出力から進捗を読み取る
            $stderr = $pipes[2];
            while (!feof($stderr)) {
                $line = fgets($stderr);
                if ($line === false) {
                    break;
                }
                // FFmpegの進捗情報を解析
                if (preg_match('/frame=\s*(\d+)/', $line, $matches)) {
                    if ($progressCallback) {
                        $progress = (int)($matches[1] / $totalFrame * 100);
                        $progressCallback($progress);
                    }
                }
            }
            if ($progressCallback) {
                $progress = 100;
                $progressCallback($progress);
            }
            // プロセスを閉じる
            fclose($stderr);
            proc_close($process);

        } catch (\Exception $e) {
            Log::error("動画エンコード中にエラーが発生しました: " . $e->getMessage());
            throw $e;
        }
    }
}