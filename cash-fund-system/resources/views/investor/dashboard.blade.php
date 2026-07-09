<x-app-layout title="لوحة المستثمر">
    <div class="space-y-6">
        <x-investor-nav />

        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">لوحة المستثمر</h1>
            <p class="mt-1 text-sm text-muted">نظرة عامة على الصندوق والطلبات</p>
        </div>

        <div class="rounded-xl border border-bdr bg-surface p-8 text-center">
            <div class="text-sm font-semibold text-muted mb-2">الرصيد الحالي للصندوق</div>
            <div class="text-4xl font-bold text-primary">{{ number_format($balance, 2) }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-primary">{{ $counts['executed'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">تم التنفيذ</div>
            </div>
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-accent">{{ $counts['pending'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">قيد المراجعة</div>
            </div>
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-accent">{{ $counts['rejected'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">مرفوض</div>
            </div>
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-muted">{{ $counts['draft'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">مسودة</div>
            </div>
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-primary">{{ $counts['approved'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">موافق عليه</div>
            </div>
            <div class="rounded-xl border border-bdr bg-surface p-5 text-center">
                <div class="text-3xl font-bold text-muted">{{ $counts['cancelled'] }}</div>
                <div class="mt-1 text-sm font-semibold text-muted">ملغى</div>
            </div>
        </div>
    </div>
</x-app-layout>
