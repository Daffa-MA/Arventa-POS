<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::query()
            ->whereKey($request->session()->get('arventa_admin_id'))
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        if (! $user) {
            $request->session()->forget('arventa_admin_id');

            return redirect()->route('admin.login');
        }

        $request->attributes->set('arventa_admin', $user);
        view()->share('currentAdmin', $user);

        return $next($request);
    }
}
