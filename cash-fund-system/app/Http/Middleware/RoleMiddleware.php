<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['username' => 'تم إيقاف حسابك، تواصل مع مدير النظام.']);
        }

        if ($user->role !== $role) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة.');
        }

        return $next($request);
    }
}
