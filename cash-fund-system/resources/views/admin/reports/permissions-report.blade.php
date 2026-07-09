<x-app-layout title="تقرير الصلاحيات">
    <div class="space-y-6">
        <x-admin-nav />

        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">تقرير الصلاحيات</h1>
                <p class="mt-1 text-sm text-muted">عدد الطلبات المنشأة والمعتمدة لكل مستخدم</p>
            </div>
            <a href="{{ route('admin.reports.permissions-report.print', request()->query()) }}"
               target="_blank"
               class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-4 py-2 text-sm font-semibold text-text transition-colors hover:bg-bg">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                طباعة
            </a>
        </div>

        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">المستخدم</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الدور</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">طلبات منشأة</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">طلبات معتمدة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($users as $user)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 font-semibold text-text">{{ $user->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                        {{ $user->role === 'admin' ? 'bg-primary/15 text-primary' : ($user->role === 'investor' ? 'bg-accent/15 text-accent' : 'bg-muted/20 text-muted') }}">
                                        {{ $user->role === 'admin' ? 'مدير' : ($user->role === 'investor' ? 'مستثمر' : 'عميل') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-text">{{ $user->created_count }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-text">{{ $user->approved_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-muted">
                                    لا يوجد مستخدمون
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
