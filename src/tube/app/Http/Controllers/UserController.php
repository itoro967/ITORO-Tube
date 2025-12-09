<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        return redirect()->route('dashboard')->with('success', 'アカウント情報を更新しました。');
    }
}
