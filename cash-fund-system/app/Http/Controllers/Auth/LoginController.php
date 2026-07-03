<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.']);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'حسابك موقوف، تواصل مع مدير النظام.']);
        }

        $user->update(['last_login_at' => now()]);

        DB::table('log_audit')->insert([
            'user_id'     => $user->id,
            'action'      => 'login',
            'entity_type' => 'users',
            'entity_id'   => $user->id,
            'created_at'  => now(),
        ]);

        return $this->redirectToDashboard($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectToDashboard($user)
    {
        $routes = [
            'admin'   => 'admin.dashboard',
            'investor'=> 'investor.dashboard',
            'client'  => 'client.dashboard',
        ];

        return redirect()->route($routes[$user->role] ?? $routes['client']);
    }
}
