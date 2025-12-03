<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function edit()
    {
        return inertia('user/edit');
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

        return redirect()->route('user.edit')->with('success', 'アカウント情報を更新しました。');
    }
}
