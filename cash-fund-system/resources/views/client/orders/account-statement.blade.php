<x-app-layout title="كشف حساب">
    <div class="mx-auto max-w-5xl space-y-6">

        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: #fff !important; }
                .print-header { display: block !important; text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #1a1a1a; }
                .print-header h1 { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
                .print-header .sub { font-size: 14px; color: #666; }
                .print-dates { display: block !important; text-align: center; margin-bottom: 15px; font-size: 13px; color: #666; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #d1d5db; padding: 8px 12px; font-size: 12px; }
                th { background-color: #f3f4f6; font-weight: 600; }
                .print-footer { display: block !important; text-align: center; margin-top: 20px; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
            }
            .print-header, .print-dates, .print-footer { display: none; }
        </style>

        {{-- Print Header (hidden on screen) --}}
        <div class="print-header">
            <h1>كشف حساب تفصيلي</h1>
            <div class="sub">نظام إدارة الصندوق النقدي</div>
        </div>

        @if (request()->hasAny(['from_date', 'to_date']))
            <div class="print-dates">
                من {{ request('from_date', '—') }} إلى {{ request('to_date', '—') }}
            </div>
        @endif

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">كشف حساب تفصيلي</h1>
                <p class="mt-1 text-sm text-muted">الطلبات المنفَّذة فقط</p>
            </div>
            <div class="flex gap-3">
                <button onclick="window.print()"
                        class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    طباعة الكشف
                </button>
                <a href="{{ route('client.orders.index') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    رجوع
                </a>
            </div>
        </div>

        {{-- Date Filter --}}
        <form method="GET" action="{{ route('client.orders.account-statement') }}"
              class="rounded-xl border border-bdr bg-surface p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-muted">من تاريخ</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                           class="rounded-lg border border-bdr bg-bg px-3 py-2 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-muted">إلى تاريخ</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                           class="rounded-lg border border-bdr bg-bg px-3 py-2 text-sm text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <button type="submit"
                        class="rounded-lg bg-primary px-5 py-2 text-sm font-bold text-white hover:brightness-110 active:scale-[0.98]">
                    بحث
                </button>
                @if (request()->hasAny(['from_date', 'to_date']))
                    <a href="{{ route('client.orders.account-statement') }}"
                       class="rounded-lg border border-bdr bg-surface px-5 py-2 text-sm font-semibold text-muted hover:bg-bg">
                        مسح الفلتر
                    </a>
                @endif
            </div>
        </form>

        {{-- Orders Table --}}
        @if ($orders->count() > 0)
            <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">رقم الطلب</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">التاريخ</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">النوع</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">المبلغ</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">تاريخ التنفيذ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @foreach ($orders as $order)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 font-mono text-xs text-text">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-muted">{{ $order->order_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                        {{ $order->type === 'payment' ? 'bg-red-500/15 text-red-400' : 'bg-green-500/15 text-green-400' }}">
                                        {{ $order->type === 'payment' ? 'صرف' : 'قبض' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-text">{{ number_format($order->amount, 2) }}</td>
                                <td class="px-4 py-3 text-muted">{{ $order->executed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-bdr bg-bg/50">
                            <td colspan="3" class="px-4 py-3 text-right font-bold text-text">الإجمالي</td>
                            <td class="px-4 py-3 font-bold text-primary">{{ number_format($orders->sum('amount'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @else
            <div class="rounded-xl border border-bdr bg-surface p-12 text-center">
                <p class="text-muted">لا توجد طلبات منفَّذة {{ request()->hasAny(['from_date', 'to_date']) ? 'within this date range' : '' }}</p>
            </div>
        @endif

        {{-- Print Footer (hidden on screen) --}}
        <div class="print-footer">
            تم طباعة هذا الكشف من نظام إدارة الصندوق النقدي — {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</x-app-layout>
