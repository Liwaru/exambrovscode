<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        $passwordMatches = $user
            && $this->passwordMatches($credentials['password'], $user->password);

        if (! $passwordMatches) {
            ActivityLog::record('login_failed', "Login gagal untuk username {$credentials['username']}.", $request, [
                'properties' => ['username' => $credentials['username']],
            ]);

            return back()->withErrors([
                'username' => 'Username atau password salah.',
            ])->onlyInput('username');
        }

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLog::record('login', "{$user->username} login ke dashboard.", $request);

        if ($request->user()->isAdmin() || $request->user()->isHeadmaster()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }

        Auth::logout();

        return back()->withErrors([
            'username' => 'Akun ini belum punya akses dashboard.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            ActivityLog::record('logout', "{$user->username} logout dari dashboard.", $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (password_get_info($storedPassword)['algo'] !== null) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
