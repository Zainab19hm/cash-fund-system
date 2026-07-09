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
                <label for="name" class="mb-1.5 block text-sm font-semibold text-text">الاسم الكامل <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="100"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أدخل الاسم الكامل" />
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- National ID --}}
            <div>
                <label for="national_id" class="mb-1.5 block text-sm font-semibold text-text">رقم الهوية الوطنية <span class="text-red-400">*</span></label>
                <input type="text" name="national_id" id="national_id" value="{{ old('national_id') }}" required maxlength="20"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أدخل رقم الهوية (أرقام فقط)" />
                @error('national_id')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Employee Number --}}
            <div>
                <label for="employee_number" class="mb-1.5 block text-sm font-semibold text-text">الرقم الوظيفي <span class="text-red-400">*</span></label>
                <input type="text" name="employee_number" id="employee_number" value="{{ old('employee_number') }}" required maxlength="20"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="مثال: EMP-0001" />
                @error('employee_number')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-text">رقم الهاتف</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" maxlength="20"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="اختياري" />
                @error('phone')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Position --}}
            <div>
                <label for="position" class="mb-1.5 block text-sm font-semibold text-text">المنصب</label>
                <input type="text" name="position" id="position" value="{{ old('position') }}" maxlength="100"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="اختياري" />
                @error('position')
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
                <label for="password" class="mb-1.5 block text-sm font-semibold text-text">كلمة المرور <span class="text-red-400">*</span></label>
                <div class="relative">
                    <input type="password" name="password" id="password" required minlength="12"
                           class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 pl-20 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                           placeholder="12 حرفاً على الأقل" />
                    <button type="button" onclick="suggestPassword('password')"
                            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-lg bg-accent/20 px-3 py-1.5 text-xs font-semibold text-accent transition-colors hover:bg-accent/30">
                        اقتراح
                    </button>
                </div>
                <div class="mt-2 rounded-lg bg-bg/50 p-3">
                    <p class="mb-1.5 text-xs font-semibold text-muted">متطلبات كلمة المرور:</p>
                    <ul class="space-y-1 text-xs text-muted">
                        <li id="pw-length" class="flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-muted"></span>
                            12 حرفاً على الأقل
                        </li>
                        <li id="pw-upper" class="flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-muted"></span>
                            حرف كبير واحد على الأقل (A-Z)
                        </li>
                        <li id="pw-lower" class="flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-muted"></span>
                            حرف صغير واحد على الأقل (a-z)
                        </li>
                        <li id="pw-number" class="flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-muted"></span>
                            رقم واحد على الأقل (0-9)
                        </li>
                        <li id="pw-special" class="flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-muted"></span>
                            رمز خاص واحد على الأقل (!@#$%^&*...)
                        </li>
                    </ul>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-text">تأكيد كلمة المرور <span class="text-red-400">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="12"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أعد إدخال كلمة المرور" />
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="mb-1.5 block text-sm font-semibold text-text">الدور <span class="text-red-400">*</span></label>
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

    @push('scripts')
    <script>
        const passwordInput = document.getElementById('password');

        function validatePassword(password) {
            const checks = {
                length: password.length >= 12,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password),
            };

            Object.entries(checks).forEach(([key, valid]) => {
                const el = document.getElementById('pw-' + key);
                if (el) {
                    const dot = el.querySelector('span');
                    if (valid) {
                        el.classList.add('text-green-400');
                        el.classList.remove('text-muted');
                        dot.classList.add('bg-green-400');
                        dot.classList.remove('bg-muted');
                    } else {
                        el.classList.remove('text-green-400');
                        el.classList.add('text-muted');
                        dot.classList.remove('bg-green-400');
                        dot.classList.add('bg-muted');
                    }
                }
            });
        }

        passwordInput?.addEventListener('input', function() {
            validatePassword(this.value);
        });

        function suggestPassword(targetId) {
            fetch('{{ route("admin.users.suggest-password") }}')
                .then(r => r.json())
                .then(data => {
                    const input = document.getElementById(targetId);
                    const confirm = document.getElementById('password_confirmation');
                    input.value = data.password;
                    input.type = 'text';
                    if (confirm) confirm.value = data.password;
                    validatePassword(data.password);
                    setTimeout(() => { input.type = 'password'; }, 2000);
                });
        }
    </script>
    @endpush
</x-app-layout>
