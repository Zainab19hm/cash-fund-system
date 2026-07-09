<x-app-layout title="طلباتي">
    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">طلباتي</h1>
                <p class="mt-1 text-sm text-muted">إدارة طلبات الصرف والقبض</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('client.orders.account-statement') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    كشف حساب
                </a>
                <a href="{{ route('client.orders.create') }}"
                   class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    + طلب جديد
                </a>
            </div>
        </div>

        {{-- Status Filter --}}
        <div class="overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                @php
                    $statuses = [
                        ''          => 'الكل',
                        'DRAFT'     => 'مسودة',
                        'PENDING'   => 'قيد المراجعة',
                        'APPROVED'  => 'معتمد',
                        'REJECTED'  => 'مرفوض',
                        'EXECUTED'  => 'منفَّذ',
                        'CANCELLED' => 'ملغى',
                    ];
                    $currentStatus = request('status', '');
                @endphp

                @foreach ($statuses as $value => $label)
                    <a href="{{ route('client.orders.index', array_filter(['status' => $value ?: null])) }}"
                       class="whitespace-nowrap rounded-lg px-4 py-2 text-xs font-semibold transition-colors
                           {{ $currentStatus === $value
                               ? 'bg-primary text-white'
                               : 'border border-bdr bg-surface text-muted hover:bg-bg hover:text-text' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Orders Table --}}
        @if ($orders->count() > 0)
            <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
                {{-- Desktop Table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-bdr bg-bg/50">
                                <th class="px-4 py-3 text-right font-semibold text-muted">رقم الطلب</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">النوع</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">المبلغ</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">الحالة</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">التاريخ</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bdr">
                            @foreach ($orders as $order)
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
                                    <td class="px-4 py-3 text-muted">{{ $order->order_date->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('client.orders.show', $order) }}"
                                               class="text-primary hover:underline">عرض</a>

                                            @if ($order->status === 'EXECUTED')
                                                <span class="text-muted">|</span>
                                                <a href="{{ route('client.orders.disbursement-voucher', $order) }}"
                                                   class="text-primary hover:underline" target="_blank">
                                                    إذن الصرف
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden divide-y divide-bdr">
                    @foreach ($orders as $order)
                        <div class="p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs text-text">{{ $order->order_number }}</span>
                                <x-status-badge :status="$order->status" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $order->type === 'payment' ? 'bg-red-500/15 text-red-400' : 'bg-green-500/15 text-green-400' }}">
                                    {{ $order->type === 'payment' ? 'صرف' : 'قبض' }}
                                </span>
                                <span class="font-semibold text-text">{{ number_format($order->amount, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-muted">
                                <span>{{ $order->order_date->format('Y-m-d') }}</span>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('client.orders.show', $order) }}"
                                       class="text-primary hover:underline">عرض</a>
                                    @if ($order->status === 'EXECUTED')
                                        <span>|</span>
                                        <a href="{{ route('client.orders.disbursement-voucher', $order) }}"
                                           class="text-primary hover:underline" target="_blank">
                                            إذن الصرف
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @else
            <div class="rounded-xl border border-bdr bg-surface p-12 text-center">
                <p class="text-muted">لا توجد طلبات بعد</p>
                <a href="{{ route('client.orders.create') }}"
                   class="mt-4 inline-block text-sm font-semibold text-primary hover:underline">
                    إنشاء أول طلب
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
