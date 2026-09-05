<!DOCTYPE html>
<html dir="rtl" lang="ar" data-role="guest" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إنشاء حساب — {{ config('app.name', 'Cash Fund') }}</title>

    {{-- Flash-prevention: read theme before paint --}}
    <script>
      (function(){
        var saved = localStorage.getItem('theme');
        var prefers = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        var theme = saved || prefers;
        document.documentElement.setAttribute('data-theme', theme);
      })();
    </script>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="min-h-screen bg-bg text-text antialiased flex items-center justify-center px-4 py-8 transition-colors duration-300">

    {{-- Theme Toggle (top-left for RTL) --}}
    <div class="fixed top-4 left-4 z-50" x-data="{ dark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }">
        <button
            @click="
                dark = !dark;
                localStorage.setItem('theme', dark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            "
            class="flex h-10 w-10 items-center justify-center rounded-full border border-bdr bg-surface shadow-sm transition-all hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            :title="dark ? 'الوضع الفاتح' : 'الوضع الداكن'"
            type="button"
        >
            <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>

    {{-- Register Card --}}
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-bdr bg-surface p-8 shadow-lg transition-colors duration-300">

            {{-- Logo / Brand --}}
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
                    <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                </div>
                <h1 class="font-heading text-2xl font-bold text-primary">إنشاء حساب جديد</h1>
                <p class="mt-2 text-sm text-muted">سيتم مراجعة حسابك وتفعيله من قبل المدير</p>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-500/20 bg-red-500/10 p-4">
                    <ul class="space-y-1 text-sm text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Register Form --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                {{-- Full Name --}}
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-text">
                        الاسم الكامل <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </span>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            autofocus
                            value="{{ old('name') }}"
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 @error('name') border-red-500 @enderror"
                            placeholder="أدخل اسمك الكامل"
                        />
                    </div>
                </div>

                {{-- Username --}}
                <div>
                    <label for="username" class="mb-1.5 block text-sm font-semibold text-text">
                        اسم المستخدم <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            value="{{ old('username') }}"
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 @error('username') border-red-500 @enderror"
                            placeholder="أدخل اسم المستخدم"
                        />
                    </div>
                </div>

                {{-- Email (optional) --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-text">
                        البريد الإلكتروني
                        <span class="text-xs font-normal text-muted">(اختياري)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 @error('email') border-red-500 @enderror"
                            placeholder="example@domain.com"
                        />
                    </div>
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="mb-1.5 block text-sm font-semibold text-text">
                        الدور <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </span>
                        <select
                            id="role"
                            name="role"
                            required
                            class="w-full appearance-none rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 @error('role') border-red-500 @enderror"
                        >
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>اختر الدور</option>
                            <option value="admin"    {{ old('role') === 'admin'    ? 'selected' : '' }}>مدير</option>
                            <option value="investor" {{ old('role') === 'investor' ? 'selected' : '' }}>مستثمر</option>
                            <option value="client"   {{ old('role') === 'client'   ? 'selected' : '' }}>عميل</option>
                        </select>
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-text">
                        كلمة المرور <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 @error('password') border-red-500 @enderror"
                            placeholder="8 أحرف على الأقل"
                        />
                    </div>
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-text">
                        تأكيد كلمة المرور <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </span>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                            placeholder="أعد إدخال كلمة المرور"
                        />
                    </div>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full rounded-xl bg-primary py-3 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                >
                    إنشاء الحساب
                </button>
            </form>

            {{-- Link to login --}}
            <p class="mt-6 text-center text-sm text-muted">
                لديك حساب بالفعل؟
                <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">
                    سجّل الدخول
                </a>
            </p>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-xs text-muted">
            {{ config('app.name', 'Cash Fund') }} &copy; {{ date('Y') }}
        </p>
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>
