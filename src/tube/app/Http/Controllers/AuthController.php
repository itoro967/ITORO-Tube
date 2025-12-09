<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return inertia('auth/signup');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        // 登録する
        User::create([
            'name' => $credentials['name'],
            'password' => bcrypt($credentials['password']),
        ]);
        return redirect()->route('auth.login');
    }

    public function login()
    {
        return inertia('auth/login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember_me'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', 'ログインしました。');
        }

        return back()->withErrors([
            'name' => '名前もしくはパスワードが違います。',
        ])->onlyInput('name');
    }
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('dashboard')->with('success', 'ログアウトしました。');
    }
}
