<x-app-layout title="تفصيل بنود الأمر">
    <div class="space-y-6">
        <x-admin-nav />

        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">تفصيل بنود الأمر</h1>
            <p class="mt-1 text-sm text-muted">تفاصيل بنود الطلب رقم {{ $order->order_number }}</p>
        </div>

        <div class="rounded-xl border border-bdr bg-surface p-5">
            <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <span class="text-muted">رقم الطلب:</span>
                    <span class="mr-2 font-semibold text-text">{{ $order->order_number }}</span>
                </div>
                <div>
                    <span class="text-muted">النوع:</span>
                    <span class="mr-2 font-semibold text-text">{{ $order->type === 'payment' ? 'صرف' : 'قبض' }}</span>
                </div>
                <div>
                    <span class="text-muted">الإجمالي:</span>
                    <span class="mr-2 font-semibold text-text">{{ number_format($order->amount, 2) }}</span>
                </div>
                <div>
                    <span class="text-muted">الحالة:</span>
                    <x-status-badge :status="$order->status" />
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">#</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">البند</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الوصف</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($items as $index => $item)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 text-muted">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-text">{{ $item->category->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->description ?? '—' }}</td>
                                <td class="px-4 py-3 font-semibold text-text">{{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-muted">
                                    لا توجد بنود لهذا الطلب
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <a href="{{ route('admin.reports.orders-status') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
            رجوع
        </a>
    </div>
</x-app-layout>
