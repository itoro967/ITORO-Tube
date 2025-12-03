<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoController;

Route::get('/top', [VideoController::class, 'index'])->name('dashboard');
Route::get('/login', [AuthController::class, 'login'])->name('login');

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/signup', [AuthController::class, 'create'])->name('create');
    Route::post('/signup', [AuthController::class, 'store'])->name('store');
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

Route::prefix('video')->name('video.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/upload', [VideoController::class, 'upload'])->name('upload');
        Route::post('/upload', [VideoController::class, 'store'])->name('store');
        Route::get('/manage', [VideoController::class, 'manage'])->name('manage');
        Route::get('/edit/{id}', [VideoController::class, 'edit'])->name('edit');
        Route::post('/edit/{id}', [VideoController::class, 'update'])->name('update');
        Route::post('/delete/{id}', [VideoController::class, 'delete'])->name('delete');
    });

    Route::get('/{id}', [VideoController::class, 'show'])->name('show');
});
Route::prefix('user')->name('user.')->middleware('auth')->group(function () {
    Route::get('/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('edit');
    Route::post('/edit', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
});