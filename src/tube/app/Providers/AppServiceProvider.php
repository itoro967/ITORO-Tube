<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ログイン試行のレート制限（名前+IP 単位で 1 分あたり 5 回まで）
        RateLimiter::for('login', function (Request $request) {
            // このコールバックは validate 前(ミドルウェア段階)で走るため、name が配列等でも安全に文字列化する
            $name = $request->input('name');
            $name = is_string($name) ? Str::lower($name) : '';
            $key = $name . '|' . $request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
