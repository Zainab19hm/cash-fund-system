<x-app-layout title="إدارة التصنيفات">
    <div class="space-y-6">

        <x-admin-nav />

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">إدارة التصنيفات</h1>
                <p class="mt-1 text-sm text-muted">تصنيفات بنود الطلبات — لكل من الصرف والقبض</p>
            </div>
            <a href="{{ route('admin.categories.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                تصنيف جديد
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4">
                <p class="text-sm text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                <ul class="space-y-1 text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.categories.index') }}" class="rounded-xl border border-bdr bg-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                {{-- Type Filter --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">النوع</label>
                    <select name="type"
                            class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                        <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>صرف</option>
                        <option value="receipt" {{ request('type') === 'receipt' ? 'selected' : '' }}>قبض</option>
                        <option value="both" {{ request('type') === 'both' ? 'selected' : '' }}>صرف وقبض</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">الحالة</label>
                    <select name="status"
                            class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>موقوف</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        بحث
                    </button>
                    <a href="{{ route('admin.categories.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>

        {{-- Categories Table --}}
        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            {{-- Desktop Table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">#</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الاسم</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">النوع</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الحالة</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($categories as $category)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 text-muted">{{ $category->id }}</td>
                                <td class="px-4 py-3 font-semibold text-text">{{ $category->name }}</td>
                                <td class="px-4 py-3">
                                    @if ($category->type === 'payment')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-accent">صرف</span>
                                    @elseif ($category->type === 'receipt')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">قبض</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">صرف وقبض</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($category->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                            نشط
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">
                                            <span class="h-1.5 w-1.5 rounded-full bg-muted"></span>
                                            موقوف
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-bdr bg-surface px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg"
                                           title="تعديل">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            تعديل
                                        </a>

                                        {{-- Toggle Status --}}
                                        <form method="POST" action="{{ route('admin.categories.toggle-status', $category) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="{{ $category->is_active ? "return confirm('هل أنت متأكد من إيقاف التصنيف $category->name؟')" : 'return true' }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors {{ $category->is_active ? 'border-red-500/20 text-red-400 hover:bg-red-500/10' : 'border-green-500/20 text-green-400 hover:bg-green-500/10' }}"
                                                    title="{{ $category->is_active ? 'إيقاف' : 'تفعيل' }}">
                                                @if ($category->is_active)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                    إيقاف
                                                @else
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    تفعيل
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-muted">
                                    لا يوجد تصنيفات
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden divide-y divide-bdr">
                @forelse ($categories as $category)
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-text">{{ $category->name }}</span>
                            @if ($category->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">
                                    <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                    نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">
                                    <span class="h-1.5 w-1.5 rounded-full bg-muted"></span>
                                    موقوف
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            @if ($category->type === 'payment')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-accent">صرف</span>
                            @elseif ($category->type === 'receipt')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">قبض</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">صرف وقبض</span>
                            @endif
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-bdr bg-surface px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg">
                                    تعديل
                                </a>
                                <form method="POST" action="{{ route('admin.categories.toggle-status', $category) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors {{ $category->is_active ? 'border-red-500/20 text-red-400 hover:bg-red-500/10' : 'border-green-500/20 text-green-400 hover:bg-green-500/10' }}">
                                        {{ $category->is_active ? 'إيقاف' : 'تفعيل' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-muted">
                        لا يوجد تصنيفات
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($categories->hasPages())
                <div class="border-t border-bdr px-4 py-3">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
