<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return inertia('user/edit', ['user' => $user]);
    }
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $request->user()->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                // 既存のプロフィール画像を削除
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image_path = $path;
        }
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        return redirect()->route('dashboard')->with('success', 'アカウント情報を更新しました。');
    }
}
