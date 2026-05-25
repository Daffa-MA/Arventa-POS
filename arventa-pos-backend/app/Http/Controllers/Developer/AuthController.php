<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
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
        if ($request->session()->has('arventa_developer_id')) {
            return redirect()->route('developer.pos.index');
        }

        return view('developer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:120'],
        ]);

        $key = 'developer-login:'.$request->ip().':'.mb_strtolower($credentials['login']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => ['Terlalu banyak percobaan. Coba lagi beberapa saat.'],
            ]);
        }

        $user = $this->findDeveloper($credentials['login'], $credentials['password']);

        if (! $user) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => ['Username/email atau password developer tidak valid.'],
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('arventa_developer_id', $user->id);

        return redirect()->intended(route('developer.pos.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('arventa_developer_id');
        $request->session()->regenerateToken();

        return redirect()->route('developer.login')->with('status', 'Berhasil logout dari Developer Console.');
    }

    private function findDeveloper(string $login, string $password): ?User
    {
        $user = User::query()
            ->where('role', 'developer')
            ->where('is_active', true)
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        $envUsername = config('services.arventa_developer.username');
        $envEmail = config('services.arventa_developer.email');
        $envPassword = config('services.arventa_developer.password');

        if (! $envPassword || ! in_array($login, array_filter([$envUsername, $envEmail]), true) || ! hash_equals($envPassword, $password)) {
            return null;
        }

        return User::query()->updateOrCreate([
            'username' => $envUsername,
        ], [
            'name' => config('services.arventa_developer.name', 'Arventa Developer'),
            'email' => $envEmail ?: 'developer@arventa.local',
            'password' => $envPassword,
            'role' => 'developer',
            'is_active' => true,
        ]);
    }
}
