@php
    $currentRoute = request()->route()->getName();
@endphp

<nav class="mb-6 flex items-center gap-1 rounded-xl border border-bdr bg-surface p-1.5" aria-label="قائمة إدارة النظام">
    <a href="{{ route('admin.users.index') }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ str_starts_with($currentRoute, 'admin.users') ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
        </svg>
        إدارة المستخدمين
    </a>
    <a href="{{ route('admin.permissions.index') }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ str_starts_with($currentRoute, 'admin.permissions') ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-bg hover:text-text' }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
        إدارة الصلاحيات
    </a>
</nav>
