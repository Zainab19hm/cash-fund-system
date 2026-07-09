<x-app-layout title="لوحة التحكم — التقارير">
    <div class="space-y-6">
        <x-admin-nav />

        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">لوحة التحكم</h1>
            <p class="mt-1 text-sm text-muted">نظرة عامة على حالة الطلبات</p>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <a href="{{ route('admin.reports.orders-status', ['status' => 'EXECUTED']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-3xl font-bold text-primary">{{ $counts['executed'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">تم التنفيذ</div>
            </a>
            <a href="{{ route('admin.reports.orders-status', ['status' => 'PENDING']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-accent/30">
                <div class="text-3xl font-bold text-accent">{{ $counts['pending'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">قيد المراجعة</div>
            </a>
            <a href="{{ route('admin.reports.orders-status', ['status' => 'REJECTED']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-accent/30">
                <div class="text-3xl font-bold text-accent">{{ $counts['rejected'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">مرفوض</div>
            </a>
            <a href="{{ route('admin.reports.orders-status', ['status' => 'DRAFT']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-muted/30">
                <div class="text-3xl font-bold text-muted">{{ $counts['draft'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">مسودة</div>
            </a>
            <a href="{{ route('admin.reports.orders-status', ['status' => 'APPROVED']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-3xl font-bold text-primary">{{ $counts['approved'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">موافق عليه</div>
            </a>
            <a href="{{ route('admin.reports.orders-status', ['status' => 'CANCELLED']) }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-muted/30">
                <div class="text-3xl font-bold text-muted">{{ $counts['cancelled'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">ملغى</div>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('admin.reports.daily-journal') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">يومية الصندوق</div>

            </a>
            <a href="{{ route('admin.reports.current-balance') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">الرصيد الحالي</div>

            </a>
            <a href="{{ route('admin.reports.movement-statement') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">كشف الحركة لفترة</div>

            </a>
            <a href="{{ route('admin.reports.totals') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">إجمالي الصرف والقبض</div>

            </a>
            <a href="{{ route('admin.reports.expenses-by-category') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">الصرف حسب البند</div>

            </a>
            <a href="{{ route('admin.reports.missing-documents') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">الوثائق الناقصة</div>

            </a>
            <a href="{{ route('admin.reports.documents-archive') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">حالة أرشفة الوثائق</div>

            </a>
            <a href="{{ route('admin.reports.user-activity') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">نشاط المستخدمين</div>

            </a>
            <a href="{{ route('admin.reports.permissions-report') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">تقرير الصلاحيات</div>

            </a>
            <a href="{{ route('admin.reports.audit-trail') }}"
               class="rounded-xl border border-bdr bg-surface p-5 transition-all hover:shadow-md hover:border-primary/30">
                <div class="text-sm font-bold text-text">سجل التدقيق</div>

            </a>
        </div>
    </div>
</x-app-layout>
