<x-app-layout title="تفاصيل الطلب">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">تفاصيل الطلب</h1>
                <p class="mt-1 text-sm text-muted">{{ $order->order_number }}</p>
            </div>
            <a href="{{ route('client.orders.index') }}"
               class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                رجوع
            </a>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Order Info --}}
        <div class="rounded-xl border border-bdr bg-surface p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-muted">رقم الطلب</span>
                    <p class="mt-1 font-mono text-sm font-semibold text-text">{{ $order->order_number }}</p>
                </div>
                <div>
                    <span class="text-sm text-muted">الحالة</span>
                    <div class="mt-1"><x-status-badge :status="$order->status" /></div>
                </div>
                <div>
                    <span class="text-sm text-muted">النوع</span>
                    <p class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $order->type === 'payment' ? 'bg-red-500/15 text-red-400' : 'bg-green-500/15 text-green-400' }}">
                            {{ $order->type === 'payment' ? 'صرف' : 'قبض' }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-sm text-muted">المبلغ الإجمالي</span>
                    <p class="mt-1 text-lg font-bold text-primary">{{ number_format($order->amount, 2) }}</p>
                </div>
                <div>
                    <span class="text-sm text-muted">تاريخ الطلب</span>
                    <p class="mt-1 text-sm text-text">{{ $order->order_date->format('Y-m-d') }}</p>
                </div>
                <div>
                    <span class="text-sm text-muted">تاريخ الإنشاء</span>
                    <p class="mt-1 text-sm text-text">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            @if ($order->description)
                <div class="border-t border-bdr pt-4">
                    <span class="text-sm text-muted">الوصف</span>
                    <p class="mt-1 text-sm text-text">{{ $order->description }}</p>
                </div>
            @endif

            @if ($order->notes)
                <div class="border-t border-bdr pt-4">
                    <span class="text-sm text-muted">ملاحظات</span>
                    <p class="mt-1 text-sm text-text">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Items --}}
        <div class="rounded-xl border border-bdr bg-surface p-6">
            <h2 class="mb-4 font-heading text-lg font-bold text-text">بنود الطلب</h2>

            @if ($order->items->count() > 0)
                <div class="overflow-hidden rounded-xl border border-bdr">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-bdr bg-bg/50">
                                <th class="px-4 py-3 text-right font-semibold text-muted">#</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">التصنيف</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">الوصف</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bdr">
                            @foreach ($order->items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 text-muted">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-text">{{ $item->category->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text">{{ $item->description }}</td>
                                    <td class="px-4 py-3 font-semibold text-text">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-bdr bg-bg/50">
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-text">الإجمالي</td>
                                <td class="px-4 py-3 font-bold text-primary">{{ number_format($order->items->sum('amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-sm text-muted">لا توجد بنود</p>
            @endif
        </div>

        {{-- Documents --}}
        <div class="rounded-xl border border-bdr bg-surface p-6">
            <h2 class="mb-4 font-heading text-lg font-bold text-text">الوثائق</h2>

            @if ($order->documents->count() > 0)
                <div class="space-y-2">
                    @foreach ($order->documents as $doc)
                        <div class="flex items-center justify-between rounded-xl border border-bdr bg-bg/50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-text">{{ $doc->file_name }}</p>
                                    <p class="text-xs text-muted">{{ strtoupper($doc->file_type) }} — {{ number_format($doc->file_size / 1024, 1) }} KB</p>
                                </div>
                            </div>
                            <span class="text-xs text-muted">{{ $doc->uploaded_at->format('Y-m-d H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-muted">لا توجد وثائق مرفقة</p>
            @endif
        </div>

        {{-- Actions --}}
        @if (in_array($order->status, ['DRAFT']))
            <div class="flex flex-wrap gap-3">
                {{-- Submit for Approval --}}
                <form method="POST" action="{{ route('client.orders.submit', $order) }}"
                      onsubmit="return confirm('هل أنت متأكد من إرسال هذا الطلب للاعتماد؟')">
                    @csrf
                    <button type="submit"
                            class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        إرسال للاعتماد
                    </button>
                </form>

                {{-- Cancel --}}
                <form method="POST" action="{{ route('client.orders.cancel', $order) }}"
                      onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟')">
                    @csrf
                    <button type="submit"
                            class="rounded-xl border border-red-500/20 bg-red-500/10 px-6 py-2.5 text-sm font-bold text-red-400 transition-all hover:bg-red-500/20 active:scale-[0.98]">
                        إلغاء الطلب
                    </button>
                </form>

                {{-- Upload Document --}}
                <form method="POST" action="{{ route('client.orders.upload-document', $order) }}"
                      enctype="multipart/form-data"
                      onsubmit="return confirm('هل أنت متأكد من رفع هذه الوثيقة؟')">
                    @csrf
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-bdr bg-surface px-6 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        رفع وثيقة
                        <input type="file" name="file" class="hidden"
                               accept=".pdf,.jpg,.jpeg,.png,.docx"
                               onchange="this.form.submit()" />
                    </label>
                </form>
            </div>
        @endif

        {{-- Cancel (for PENDING status) --}}
        @if ($order->status === 'PENDING')
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('client.orders.cancel', $order) }}"
                      onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟')">
                    @csrf
                    <button type="submit"
                            class="rounded-xl border border-red-500/20 bg-red-500/10 px-6 py-2.5 text-sm font-bold text-red-400 transition-all hover:bg-red-500/20 active:scale-[0.98]">
                        إلغاء الطلب
                    </button>
                </form>
            </div>
        @endif

        {{-- Upload Document (for non-DRAFT, non-blocked statuses) --}}
        @if (!in_array($order->status, ['DRAFT', 'EXECUTED', 'CANCELLED', 'REJECTED']))
            <form method="POST" action="{{ route('client.orders.upload-document', $order) }}"
                  enctype="multipart/form-data"
                  onsubmit="return confirm('هل أنت متأكد من رفع هذه الوثيقة؟')">
                @csrf
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-bdr bg-surface px-6 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    رفع وثيقة
                    <input type="file" name="file" class="hidden"
                           accept=".pdf,.jpg,.jpeg,.png,.docx"
                           onchange="this.form.submit()" />
                </label>
            </form>
        @endif

        {{-- Print Voucher (EXECUTED orders only) --}}
        @if ($order->status === 'EXECUTED')
            <div class="flex flex-wrap gap-3">
                @if ($order->type === 'payment')
                    <a href="{{ route('client.orders.disbursement-voucher', $order) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        طباعة إذن الصرف
                    </a>
                @elseif ($order->type === 'receipt')
                    <a href="{{ route('client.orders.receipt-voucher', $order) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        طباعة فاتورة القبض
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
