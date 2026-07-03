<x-app-layout title="إدارة المستخدمين">
    <div class="space-y-6">

        <x-admin-nav />

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">إدارة المستخدمين</h1>
                <p class="mt-1 text-sm text-muted">عرض وإدارة حسابات المستخدمين في النظام</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                مستخدم جديد
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4">
                <p class="text-sm text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                <ul class="space-y-1 text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.users.index') }}" class="rounded-xl border border-bdr bg-surface p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                {{-- Search --}}
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-text">بحث</label>
                    <div class="relative">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="الاسم أو اسم المستخدم..."
                               class="w-full rounded-xl border border-bdr bg-bg py-2.5 pr-10 pl-4 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                    </div>
                </div>

                {{-- Role Filter --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">الدور</label>
                    <select name="role"
                            class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                        <option value="all" {{ request('role', 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>مدير النظام</option>
                        <option value="investor" {{ request('role') === 'investor' ? 'selected' : '' }}>مستثمر</option>
                        <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>عميل</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-text">الحالة</label>
                    <select name="status"
                            class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>موقوف</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    بحث
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    إعادة ضبط
                </a>
            </div>
        </form>

        {{-- Users Table --}}
        <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-bdr bg-bg/50">
                            <th class="px-4 py-3 text-right font-semibold text-muted">#</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الاسم</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">اسم المستخدم</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الدور</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted">آخر دخول</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bdr">
                        @forelse ($users as $user)
                            <tr class="transition-colors hover:bg-bg/50">
                                <td class="px-4 py-3 text-muted">{{ $user->id }}</td>
                                <td class="px-4 py-3 font-semibold text-text">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-muted">{{ $user->username }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->role === 'admin')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">مدير النظام</span>
                                    @elseif ($user->role === 'investor')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-accent">مستثمر</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">عميل</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 px-3 py-1 text-xs font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                            نشط
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/20 px-3 py-1 text-xs font-semibold text-muted">
                                            <span class="h-1.5 w-1.5 rounded-full bg-muted"></span>
                                            موقوف
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-muted">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Edit --}}
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-bdr bg-surface px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg"
                                           title="تعديل">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            تعديل
                                        </a>

                                        {{-- Reset Password --}}
                                        <button type="button"
                                                @click="$dispatch('open-reset-password', { userId: {{ $user->id }}, userName: '{{ $user->name }}' })"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-bdr bg-surface px-3 py-1.5 text-xs font-semibold text-text transition-colors hover:bg-bg"
                                                title="إعادة تعيين كلمة المرور">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                        </button>

                                        {{-- Toggle Status --}}
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}"
                                              x-data
                                              @submit.prevent="
                                                  if (!{{ $user->is_active ? 'true' : 'false' }}) {
                                                      $el.submit();
                                                  } else {
                                                      if (confirm('هل أنت متأكد من إيقاف حساب \"{{ $user->name }}\"؟')) {
                                                          $el.submit();
                                                      }
                                                  }
                                              "
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors {{ $user->is_active ? 'border-red-500/20 text-red-400 hover:bg-red-500/10' : 'border-green-500/20 text-green-400 hover:bg-green-500/10' }}"
                                                    title="{{ $user->is_active ? 'إيقاف' : 'تفعيل' }}">
                                                @if ($user->is_active)
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                    إيقاف
                                                @else
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    تفعيل
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-muted">
                                    لا يوجد مستخدمين
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($users->hasPages())
                <div class="border-t border-bdr px-4 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Reset Password Modal --}}
    <div x-data="{ show: false, userId: null, userName: '' }"
         x-show="show"
         @open-reset-password.window="show = true; userId = $event.detail.userId; userName = $event.detail.userName"
         @keydown.escape.window="show = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-bdr bg-surface p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-heading text-lg font-bold text-primary">إعادة تعيين كلمة المرور</h3>
                <button @click="show = false" class="text-muted hover:text-text">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="'{{ url('admin/users') }}/' + userId + '/reset-password'" method="POST">
                @csrf
                <div class="mb-4">
                    <p class="text-sm text-muted">كلمة المرور الجديدة للمستخدم: <strong x-text="userName" class="text-text"></strong></p>
                </div>

                <div class="mb-4">
                    <label for="reset_password" class="mb-1.5 block text-sm font-semibold text-text">كلمة المرور الجديدة</label>
                    <input type="password" name="password" id="reset_password" required minlength="8"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                           placeholder="8 أحرف على الأقل" />
                </div>

                <div class="mb-6">
                    <label for="reset_password_confirmation" class="mb-1.5 block text-sm font-semibold text-text">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" id="reset_password_confirmation" required minlength="8"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                           placeholder="أعد إدخال كلمة المرور" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="show = false"
                            class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إلغاء
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        إعادة التعيين
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
