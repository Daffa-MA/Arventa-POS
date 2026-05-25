<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloperAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::query()
            ->whereKey($request->session()->get('arventa_developer_id'))
            ->where('role', 'developer')
            ->where('is_active', true)
            ->first();

        if (! $user) {
            $request->session()->forget('arventa_developer_id');

            return redirect()->route('developer.login');
        }

        $request->attributes->set('arventa_developer', $user);
        view()->share('currentDeveloper', $user);

        return $next($request);
    }
}
