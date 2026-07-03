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
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4">
            <a href="/" class="font-heading text-lg font-bold text-primary">
                {{ config('app.name', 'Cash Fund') }}
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-accent hover:underline">خروج</button>
                    </form>
                @endauth

                <x-theme-toggle />
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="mx-auto max-w-7xl px-4 py-6">
        {{ $slot }}
    </main>

    <script src="{{ mix('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
