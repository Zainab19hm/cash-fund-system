<x-app-layout title="إدارة الطلبات">
    <div class="space-y-6">

        <x-admin-nav />

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">إدارة الطلبات</h1>
                <p class="mt-1 text-sm text-muted">عرض ومعالجة طلبات الصرف والقبض</p>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.orders.index') }}" class="rounded-xl border border-bdr bg-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">الحالة</label>
                    <select name="status"
                            class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>موافق عليه</option>
                        <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>مرفوض</option>
                        <option value="EXECUTED" {{ request('status') === 'EXECUTED' ? 'selected' : '' }}>تم التنفيذ</option>
                        <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>ملغى</option>
                        <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>مسودة</option>
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        بحث
                    </button>
                    <a href="{{ route('admin.orders.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>

        {{-- Orders Table --}}
        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">رقم الطلب</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">النوع</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">المبلغ</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">المنشئ</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">التاريخ</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($orders as $order)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 font-mono text-xs text-text">{{ $order->order_number }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                        {{ $order->type === 'payment' ? 'bg-red-500/15 text-red-400' : 'bg-green-500/15 text-green-400' }}">
                                        {{ $order->type === 'payment' ? 'صرف' : 'قبض' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-text">{{ number_format($order->amount, 2) }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$order->status" /></td>
                                <td class="px-4 py-3 text-muted">{{ $order->creator->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-muted">{{ $order->order_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="text-primary hover:underline">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-muted">
                                    لا توجد طلبات
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-bdr px-4 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
