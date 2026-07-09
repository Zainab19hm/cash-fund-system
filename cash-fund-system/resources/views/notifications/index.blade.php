<x-app-layout title="الإشعارات">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">الإشعارات</h1>
                <p class="mt-1 text-sm text-muted">{{ $notifications->total() }} إشعار</p>
            </div>
            <a href="{{ url()->previous() }}"
               class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                رجوع
            </a>
        </div>

        {{-- Notifications List --}}
        @if ($notifications->count() > 0)
            <div class="space-y-3">
                @foreach ($notifications as $notification)
                    <div class="rounded-xl border bg-surface p-4 transition-colors
                        {{ $notification->is_read ? 'border-bdr' : 'border-primary/30 bg-primary/5' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    {{-- Type Badge --}}
                                    @if ($notification->type === 'APPROVED')
                                        <span class="inline-flex items-center rounded-full bg-green-500/15 px-2.5 py-0.5 text-xs font-semibold text-green-400">
                                            موافق عليه
                                        </span>
                                    @elseif ($notification->type === 'REJECTED')
                                        <span class="inline-flex items-center rounded-full bg-red-500/15 px-2.5 py-0.5 text-xs font-semibold text-red-400">
                                            مرفوض
                                        </span>
                                    @elseif ($notification->type === 'EXECUTED')
                                        <span class="inline-flex items-center rounded-full bg-blue-500/15 px-2.5 py-0.5 text-xs font-semibold text-blue-400">
                                            تم التنفيذ
                                        </span>
                                    @elseif ($notification->type === 'NEW_ORDER')
                                        <span class="inline-flex items-center rounded-full bg-yellow-500/15 px-2.5 py-0.5 text-xs font-semibold text-yellow-400">
                                            طلب جديد
                                        </span>
                                    @endif

                                    {{-- Unread Indicator --}}
                                    @if (!$notification->is_read)
                                        <span class="h-2 w-2 rounded-full bg-primary"></span>
                                    @endif
                                </div>

                                <p class="mt-2 text-sm text-text">{{ $notification->message }}</p>

                                <div class="mt-2 flex items-center gap-4 text-xs text-muted">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    @if ($notification->read_at)
                                        <span>مقروءة في {{ $notification->read_at->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- View Order Link --}}
                                @if (auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.orders.show', $notification->order_id) }}"
                                       class="rounded-lg border border-bdr bg-bg/50 px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg">
                                        عرض الطلب
                                    </a>
                                @elseif (auth()->user()->role === 'client')
                                    <a href="{{ route('client.orders.show', $notification->order_id) }}"
                                       class="rounded-lg border border-bdr bg-bg/50 px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg">
                                        عرض الطلب
                                    </a>
                                @endif

                                {{-- Mark as Read Button --}}
                                @if (!$notification->is_read)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit"
                                                class="rounded-lg border border-bdr bg-bg/50 px-3 py-1.5 text-xs font-semibold text-muted transition-colors hover:bg-bg hover:text-text">
                                            تمييز كمقروءة
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="rounded-xl border border-bdr bg-surface p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-text">لا توجد إشعارات</h3>
                <p class="mt-2 text-sm text-muted">لم يُسجل أي إشعار لك بعد</p>
            </div>
        @endif
    </div>
</x-app-layout>
