<x-app-layout title="تفاصيل الطلب - الإدارة">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">تفاصيل الطلب</h1>
                <p class="mt-1 text-sm text-muted">{{ $order->order_number }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.report', $order) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    طباعة تقرير
                </a>
                <a href="{{ route('admin.orders.index') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    رجوع
                </a>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Errors --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                <ul class="space-y-1 text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
                <div>
                    <span class="text-sm text-muted">المنشئ</span>
                    <p class="mt-1 text-sm text-text">{{ $order->creator->name ?? '—' }}</p>
                </div>
                @if ($order->type === 'receipt' && $order->payer_name)
                    <div>
                        <span class="text-sm text-muted">اسم الدافع</span>
                        <p class="mt-1 text-sm font-semibold text-primary">{{ $order->payer_name }}</p>
                    </div>
                @endif
                @if ($order->approved_by)
                    <div>
                        <span class="text-sm text-muted">اعتمد بواسطة</span>
                        <p class="mt-1 text-sm text-text">{{ $order->approver->name ?? '—' }}</p>
                    </div>
                @endif
                @if ($order->executed_by)
                    <div>
                        <span class="text-sm text-muted">نُفّذ بواسطة</span>
                        <p class="mt-1 text-sm text-text">{{ $order->executor->name ?? '—' }}</p>
                    </div>
                @endif
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

            @if ($order->rejection_reason)
                <div class="border-t border-bdr pt-4">
                    <span class="text-sm text-muted">سبب الرفض</span>
                    <p class="mt-1 text-sm text-red-400">{{ $order->rejection_reason }}</p>
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
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-muted">{{ $doc->uploaded_at->format('Y-m-d H:i') }}</span>
                                <a href="{{ route('admin.orders.download-document', [$order, $doc]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-primary/20"
                                   title="تحميل الملف">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    تحميل
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-muted">لا توجد وثائق مرفقة</p>
            @endif
        </div>

        {{-- Actions --}}
        @if ($order->status === 'PENDING')
            <div class="rounded-xl border border-bdr bg-surface p-6 space-y-4">
                <h2 class="font-heading text-lg font-bold text-text">إجراءات الاعتماد</h2>

                <div class="flex flex-wrap gap-3">
                    {{-- Approve --}}
                    <form method="POST" action="{{ route('admin.orders.approve', $order) }}"
                          onsubmit="return confirm('هل أنت متأكد من اعتماد هذا الطلب؟')">
                        @csrf
                        <button type="submit"
                                class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110 active:scale-[0.98]">
                            اعتماد الطلب
                        </button>
                    </form>

                    {{-- Reject --}}
                    <button type="button"
                            onclick="document.getElementById('reject-modal').style.display='flex'"
                            class="rounded-xl bg-red-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110 active:scale-[0.98]">
                        رفض الطلب
                    </button>
                </div>
            </div>
        @endif

        {{-- Execute Action --}}
        @if ($order->status === 'APPROVED')
            <div class="rounded-xl border border-bdr bg-surface p-6 space-y-4">
                <h2 class="font-heading text-lg font-bold text-text">تنفيذ الطلب</h2>
                <p class="text-sm text-muted">الطلب معتمد وجاهز للتنفيذ. التأكيد صريح ومنفصل.</p>

                <button type="button"
                        onclick="document.getElementById('execute-modal').style.display='flex'"
                        class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    تنفيذ الطلب
                </button>
            </div>
        @endif

    </div>

    {{-- Reject Modal --}}
    <div id="reject-modal"
         class="fixed inset-0 z-50 items-center justify-center p-4"
         style="display: none;">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('reject-modal').style.display='none'"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-bdr bg-surface p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-heading text-lg font-bold text-primary">رفض الطلب</h3>
                <button onclick="document.getElementById('reject-modal').style.display='none'" class="text-muted hover:text-text">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.orders.reject', $order) }}">
                @csrf
                <div class="mb-4">
                    <label for="rejection_reason" class="mb-1.5 block text-sm font-semibold text-text">سبب الرفض <span class="text-red-400">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required
                              class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                              placeholder="اكتب سبب الرفض..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="document.getElementById('reject-modal').style.display='none'"
                            class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110 active:scale-[0.98]">
                        تأكيد الرفض
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Execute Confirmation Modal --}}
    <div id="execute-modal"
         class="fixed inset-0 z-50 items-center justify-center p-4"
         style="display: none;">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('execute-modal').style.display='none'"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-bdr bg-surface p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-heading text-lg font-bold text-primary">تأكيد التنفيذ</h3>
                <button onclick="document.getElementById('execute-modal').style.display='none'" class="text-muted hover:text-text">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <p class="text-sm text-muted">لتنفيذ هذا الطلب، يرجى كتابة <strong class="text-text">"EXECUTE"</strong> للتأكيد.</p>
                <p class="mt-2 text-sm text-muted">رقم الطلب: <span class="font-mono font-semibold text-text">{{ $order->order_number }}</span></p>
                <p class="text-sm text-muted">المبلغ: <span class="font-semibold text-primary">{{ number_format($order->amount, 2) }}</span></p>
            </div>

            <form method="POST" action="{{ route('admin.orders.execute', $order) }}">
                @csrf
                <div class="mb-4">
                    <input type="text" name="confirm_execute" id="confirm_execute_input"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                           placeholder="اكتب EXECUTE للتأكيد"
                           oninput="document.getElementById('execute-submit-btn').disabled = (this.value !== 'EXECUTE')" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="document.getElementById('execute-modal').style.display='none'"
                            class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إلغاء
                    </button>
                    <button type="submit" id="execute-submit-btn" disabled
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                        تنفيذ الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
