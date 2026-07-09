<!DOCTYPE html>
<html dir="rtl" lang="ar"
      data-role="{{ auth()->user()->role ?? 'client' }}"
      data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Cash Fund') }}</title>

    {{-- Flash-prevention: read theme before paint --}}
    <script>
      (function(){
        var saved = localStorage.getItem('theme');
        var prefers = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        var theme = saved || prefers;
        document.documentElement.setAttribute('data-theme', theme);
      })();
    </script>

    {{-- Google Fonts: IBM Plex Sans Arabic + Tajawal --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">

    @stack('styles')
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="min-h-screen bg-bg text-text antialiased">

    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-bdr bg-surface/80 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="font-heading text-lg font-bold text-primary">
                {{ config('app.name', 'Cash Fund') }}
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative text-muted hover:text-text transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span id="notification-badge" class="absolute -top-1 -right-1 hidden min-h-[18px] min-w-[18px] rounded-full bg-primary px-1 text-[10px] font-bold text-white flex items-center justify-center"></span>
                    </a>
                    <span class="hidden text-sm text-muted sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline"
                          onsubmit="return confirm('هل أنت متأكد من تسجيل الخروج؟')">
                        @csrf
                        <button type="submit" class="text-sm text-accent hover:underline">خروج</button>
                    </form>
                @endauth

                <x-theme-toggle />
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <script src="{{ mix('js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const badge = document.getElementById('notification-badge');
            if (!badge) return;

            function fetchUnreadCount() {
                fetch('/notifications/unread-count', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    })
                    .catch(() => {});
            }

            fetchUnreadCount();
            setInterval(fetchUnreadCount, 30000);
        });
    </script>
    @stack('scripts')
    <script>Alpine.start();</script>
</body>
</html>
