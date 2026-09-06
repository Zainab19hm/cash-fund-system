<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email'],
            // FIX #3: pass name+username to StrongPassword so it can block
            // them even when no user is authenticated yet (registration context).
            // FIX: min raised to 12 to match StrongPassword requirements.
            'password' => ['required', 'string', 'min:12', 'confirmed',
                           new StrongPassword($request->input('name'), $request->input('username'))],
        ], [
            'name.required'      => 'الاسم الكامل مطلوب.',
            'name.max'           => 'الاسم لا يجب أن يتجاوز 100 حرف.',
            'username.required'  => 'اسم المستخدم مطلوب.',
            'username.max'       => 'اسم المستخدم لا يجب أن يتجاوز 100 حرف.',
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.email'        => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required'  => 'كلمة المرور مطلوبة.',
            'password.min'       => 'كلمة المرور يجب أن تكون 12 حرفاً على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        // FIX #2: role is always 'client' for self-registration.
        // The role field is removed from the form; it must never be
        // user-supplied because it would allow privilege escalation.
        User::create([
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'] ?? null,
            'password'  => $validated['password'],
            'role'      => 'client',
            'is_active' => false,
        ]);

        return redirect()->route('login')
            ->with('success', 'تم إنشاء حسابك بنجاح. يرجى انتظار تفعيله من قبل مدير النظام.');
    }
}
