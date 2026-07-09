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
            // 入力動画の総再生時間(秒)を取得する（全フレームをデコードせず高速に取得）
            $duration = 0.0;
            $ffprobeCommand = "ffprobe -v error -show_entries format=duration -of default=nokey=1:noprint_wrappers=1 " . escapeshellarg($inputFile);
            $output = [];
            exec($ffprobeCommand, $output, $returnVar);
            if ($returnVar === 0 && isset($output[0]) && is_numeric(trim($output[0]))) {
                $duration = (float) trim($output[0]);
            } else {
                // 再生時間が取れないと進捗を算出できない（0%のまま完了まで進む）。握りつぶさず記録する。
                Log::warning("動画の再生時間を取得できませんでした。進捗は表示されません。Input: {$inputFile}");
            }

            // FFmpegを使用して動画をエンコード
            // -movflags +faststart で moov アトムを先頭に移動し、Web ストリーミング時の即時再生・シークを可能にする
            $command = "ffmpeg -y -i " . escapeshellarg($inputFile)
                . " -c:v libx264 -crf 23 -c:a aac -movflags +faststart -progress pipe:2 "
                . escapeshellarg($outputFile);
            // プロセスを開始
            // 標準出力(pipe:1)は読み取らないためパイプを開かない（未読パイプによる fd リーク・デッドロックを回避）。
            $descriptors = [
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
                // FFmpegの進捗情報(out_time_us)を解析し、再生時間に対する割合で進捗を算出する
                if ($duration > 0 && $progressCallback && preg_match('/out_time_us=(\d+)/', $line, $matches)) {
                    $progress = (int) min(99, ($matches[1] / 1_000_000) / $duration * 100);
                    $progressCallback($progress);
                }
            }
            // プロセスを閉じる
            fclose($stderr);
            $exitCode = proc_close($process);
            if ($exitCode !== 0) {
                throw new \Exception("FFmpegエンコードが異常終了しました。終了コード: {$exitCode}");
            }
            // 正常終了を確認できてから完了(100%)を通知する（失敗を完了扱いしない）
            if ($progressCallback) {
                $progressCallback(100);
            }

        } catch (\Exception $e) {
            Log::error("動画エンコード中にエラーが発生しました: " . $e->getMessage());
            throw $e;
        }
    }
}