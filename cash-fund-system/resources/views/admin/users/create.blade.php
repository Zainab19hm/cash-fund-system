<x-app-layout title="مستخدم جديد">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">مستخدم جديد</h1>
            <p class="mt-1 text-sm text-muted">إضافة مستخدم جديد إلى النظام</p>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                <ul class="space-y-1 text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-xl border border-bdr bg-surface p-6 space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-text">الاسم الكامل</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أدخل الاسم الكامل" />
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Username --}}
            <div>
                <label for="username" class="mb-1.5 block text-sm font-semibold text-text">اسم المستخدم</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" required maxlength="100"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أدخل اسم المستخدم (فريد)" />
                @error('username')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-text">كلمة المرور</label>
                <input type="password" name="password" id="password" required minlength="8"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="8 أحرف على الأقل" />
                @error('password')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-text">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أعد إدخال كلمة المرور" />
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="mb-1.5 block text-sm font-semibold text-text">الدور</label>
                <select name="role" id="role" required
                        class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                    <option value="">اختر الدور...</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>مدير النظام</option>
                    <option value="investor" {{ old('role') === 'investor' ? 'selected' : '' }}>مستثمر</option>
                    <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>عميل</option>
                </select>
                @error('role')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    إلغاء
                </a>
                <button type="submit"
                        class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    إنشاء المستخدم
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
