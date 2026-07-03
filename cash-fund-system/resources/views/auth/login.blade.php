<!DOCTYPE html>
<html dir="rtl" lang="ar" data-role="client" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول — {{ config('app.name', 'Cash Fund') }}</title>

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
<body class="min-h-screen bg-bg text-text antialiased flex items-center justify-center px-4 transition-colors duration-300">

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

    {{-- Login Card --}}
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-bdr bg-surface p-8 shadow-lg transition-colors duration-300">

            {{-- Logo / Brand --}}
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
                    <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="font-heading text-2xl font-bold text-primary">مرحباً بك</h1>
                <p class="mt-2 text-sm text-muted">سجّل دخولك للوصول إلى لوحة التحكم</p>
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

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-500/20 bg-green-500/10 p-4">
                    <p class="text-sm text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Username --}}
                <div>
                    <label for="username" class="mb-1.5 block text-sm font-semibold text-text">
                        اسم المستخدم
                    </label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </span>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            autofocus
                            value="{{ old('username') }}"
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                            placeholder="أدخل اسم المستخدم"
                        />
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-text">
                        كلمة المرور
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
                            class="w-full rounded-xl border border-bdr bg-bg py-3 pr-10 pl-4 text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                            placeholder="أدخل كلمة المرور"
                        />
                    </div>
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-bdr text-primary focus-visible:ring-2 focus-visible:ring-primary/20">
                        تذكرني
                    </label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full rounded-xl bg-primary py-3 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                >
                    تسجيل الدخول
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-xs text-muted">
            {{ config('app.name', 'Cash Fund') }} &copy; {{ date('Y') }}
        </p>
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>
