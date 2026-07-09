@php
    $currentRoute = request()->route()->getName();
@endphp

<nav class="mb-6 overflow-x-auto rounded-xl border border-bdr bg-surface p-1.5" aria-label="قائمة المستثمر">
    <div class="flex items-center gap-1 min-w-max">
        <a href="{{ route('investor.dashboard') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $currentRoute === 'investor.dashboard' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            لوحة التحكم
        </a>
        <a href="{{ route('investor.current-balance') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $currentRoute === 'investor.current-balance' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            الرصيد الحالي
        </a>
        <a href="{{ route('investor.movement-statement') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ str_starts_with($currentRoute, 'investor.movement') ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            كشف الحركة
        </a>
        <a href="{{ route('investor.totals') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $currentRoute === 'investor.totals' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            إجمالي الصرف والقبض
        </a>
        <a href="{{ route('investor.expenses-by-category') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $currentRoute === 'investor.expenses-by-category' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            الصرف حسب البند
        </a>
        <a href="{{ route('investor.pending-orders') }}"
           class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $currentRoute === 'investor.pending-orders' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
            الأوامر المعلقة
        </a>
    </div>
</nav>
