<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('arventa_admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'setting' => StoreSetting::query()->firstOrFail(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:120'],
        ]);

        $key = 'admin-login:'.$request->ip().':'.mb_strtolower($credentials['login']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => ['Terlalu banyak percobaan. Coba lagi beberapa saat.'],
            ]);
        }

        $user = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where(function ($query) use ($credentials): void {
                $query->where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login']);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => ['Username/email atau password tidak valid.'],
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('arventa_admin_id', $user->id);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('arventa_admin_id');
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Berhasil logout dari Admin.');
    }
}
