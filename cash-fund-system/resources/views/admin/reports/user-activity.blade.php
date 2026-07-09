<x-app-layout title="نشاط المستخدمين">
    <div class="space-y-6">
        <x-admin-nav />

        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">نشاط المستخدمين</h1>
                <p class="mt-1 text-sm text-muted">سجل النشاط من جدول log_audit</p>
            </div>
            <a href="{{ route('admin.reports.user-activity.print', request()->query()) }}"
               target="_blank"
               class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-4 py-2 text-sm font-semibold text-text transition-colors hover:bg-bg">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                طباعة
            </a>
        </div>

        <form method="GET" action="{{ route('admin.reports.user-activity') }}" class="rounded-xl border border-bdr bg-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">رقم المستخدم</label>
                    <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                           placeholder="اختياري" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">من تاريخ</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        بحث
                    </button>
                    <a href="{{ route('admin.reports.user-activity') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">المستخدم</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الإجراء</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">نوع الكيان</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">رقم الكيان</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">ملاحظات</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($activities as $activity)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 text-muted">{{ $activity->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-semibold text-text">{{ $activity->action }}</td>
                                <td class="px-4 py-3 text-muted">{{ $activity->entity_type }}</td>
                                <td class="px-4 py-3 text-muted">{{ $activity->entity_id }}</td>
                                <td class="px-4 py-3 text-muted">{{ $activity->notes ?? '—' }}</td>
                                <td class="px-4 py-3 text-muted">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-muted">
                                    لا توجد سجلات نشاط
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages())
                <div class="border-t border-bdr px-4 py-3">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
