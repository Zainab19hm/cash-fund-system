<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', Rule::in(['admin', 'investor', 'client'])],
        ], [
            'name.required'      => 'الاسم الكامل مطلوب.',
            'name.max'           => 'الاسم لا يجب أن يتجاوز 100 حرف.',
            'username.required'  => 'اسم المستخدم مطلوب.',
            'username.max'       => 'اسم المستخدم لا يجب أن يتجاوز 100 حرف.',
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.email'        => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required'  => 'كلمة المرور مطلوبة.',
            'password.min'       => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'role.required'      => 'الدور مطلوب.',
            'role.in'            => 'الدور المختار غير صحيح.',
        ]);

        User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'email'     => $request->email ?: null,
            'password'  => $request->password,
            'role'      => $request->role,
            'is_active' => false, // forced to false regardless of any input
        ]);

        return redirect()->route('login')
            ->with('success', 'تم إنشاء حسابك بنجاح. يرجى انتظار تفعيله من قبل مدير النظام.');
    }
}
