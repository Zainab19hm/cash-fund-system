<x-app-layout title="إدارة الصلاحيات">
    <div class="space-y-6" x-data="permissionsMatrix()">

        <x-admin-nav />

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading text-2xl font-bold text-primary">إدارة الصلاحيات</h1>
                <p class="mt-1 text-sm text-muted">منح وسحب الصلاحيات لكل دور في النظام</p>
            </div>
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

        {{-- Permissions Matrix --}}
        <form method="POST" action="{{ route('admin.permissions.update') }}" id="permissionsForm">
            @csrf

            <div class="overflow-hidden rounded-xl border border-bdr bg-surface">
                {{-- Desktop Table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-bdr bg-bg/50">
                                <th class="px-4 py-3 text-right font-semibold text-muted lg:px-6">الصلاحية</th>
                                <th class="px-4 py-3 text-center font-semibold text-muted lg:px-6">مدير النظام</th>
                                <th class="px-4 py-3 text-center font-semibold text-muted lg:px-6">مستثمر</th>
                                <th class="px-4 py-3 text-center font-semibold text-muted lg:px-6">عميل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bdr">
                            @foreach ($permissions as $perm)
                                @php
                                    $adminChecked = in_array($perm->id, $rolePermissions['admin'] ?? []);
                                    $investorChecked = in_array($perm->id, $rolePermissions['investor'] ?? []);
                                    $clientChecked = in_array($perm->id, $rolePermissions['client'] ?? []);
                                @endphp
                                <tr class="transition-colors hover:bg-bg/50">
                                    <td class="px-4 py-3 lg:px-6">
                                        <span class="font-semibold text-text">{{ $perm->label }}</span>
                                        <span class="mr-2 text-xs text-muted">({{ $perm->key }})</span>
                                    </td>
                                    <td class="px-4 py-3 text-center lg:px-6">
                                        <input type="checkbox"
                                               name="assignments[]"
                                               value="{{ json_encode(['role' => 'admin', 'permission_id' => $perm->id]) }}"
                                               {{ $adminChecked ? 'checked' : '' }}
                                               aria-label="مدير النظام — {{ $perm->label }}"
                                               class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                               @change="trackChange()" />
                                    </td>
                                    <td class="px-4 py-3 text-center lg:px-6">
                                        <input type="checkbox"
                                               name="assignments[]"
                                               value="{{ json_encode(['role' => 'investor', 'permission_id' => $perm->id]) }}"
                                               {{ $investorChecked ? 'checked' : '' }}
                                               aria-label="مستثمر — {{ $perm->label }}"
                                               class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                               @change="trackChange()" />
                                    </td>
                                    <td class="px-4 py-3 text-center lg:px-6">
                                        <input type="checkbox"
                                               name="assignments[]"
                                               value="{{ json_encode(['role' => 'client', 'permission_id' => $perm->id]) }}"
                                               {{ $clientChecked ? 'checked' : '' }}
                                               aria-label="عميل — {{ $perm->label }}"
                                               class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                               @change="trackChange()" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden">
                    @foreach ($permissions as $perm)
                        @php
                            $adminChecked = in_array($perm->id, $rolePermissions['admin'] ?? []);
                            $investorChecked = in_array($perm->id, $rolePermissions['investor'] ?? []);
                            $clientChecked = in_array($perm->id, $rolePermissions['client'] ?? []);
                        @endphp
                        <div class="border-b border-bdr p-4 last:border-b-0">
                            <div class="mb-3">
                                <span class="font-semibold text-text">{{ $perm->label }}</span>
                                <span class="mr-2 text-xs text-muted">({{ $perm->key }})</span>
                            </div>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="assignments[]"
                                           value="{{ json_encode(['role' => 'admin', 'permission_id' => $perm->id]) }}"
                                           {{ $adminChecked ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                           @change="trackChange()" />
                                    <span class="text-sm text-muted">مدير النظام</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="assignments[]"
                                           value="{{ json_encode(['role' => 'investor', 'permission_id' => $perm->id]) }}"
                                           {{ $investorChecked ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                           @change="trackChange()" />
                                    <span class="text-sm text-muted">مستثمر</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="assignments[]"
                                           value="{{ json_encode(['role' => 'client', 'permission_id' => $perm->id]) }}"
                                           {{ $clientChecked ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-bdr text-primary focus:ring-2 focus:ring-primary/20"
                                           @change="trackChange()" />
                                    <span class="text-sm text-muted">عميل</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Save Button --}}
                <div class="flex items-center justify-end gap-3 border-t border-bdr px-6 py-4">
                    <span class="text-sm text-muted" x-show="changed" x-cloak>
                        تغييرات غير محفوظة
                    </span>
                    <button type="button"
                            @click="showConfirm()"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        حفظ التغييرات
                    </button>
                </div>
            </div>
        </form>

        {{-- Confirm Modal --}}
        <div x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="confirmOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div class="fixed inset-0 bg-black/50" @click="confirmOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-bdr bg-surface p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-heading text-lg font-bold text-primary">تأكيد التحديث</h3>
                    <button @click="confirmOpen = false" class="text-muted hover:text-text">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-6 space-y-2 text-sm text-muted">
                    <p>سيتم تطبيق التغييرات التالية على جميع الأدوار:</p>
                    <template x-if="addedCount > 0">
                        <p class="text-green-400">منح صلاحية: <strong x-text="addedCount"></strong></p>
                    </template>
                    <template x-if="removedCount > 0">
                        <p class="text-red-400">سحب صلاحية: <strong x-text="removedCount"></strong></p>
                    </template>
                    <template x-if="addedCount === 0 && removedCount === 0">
                        <p class="text-muted">لا توجد تغييرات</p>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="confirmOpen = false"
                            class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                        إلغاء
                    </button>
                    <button type="button" @click="submitForm()"
                            :disabled="addedCount === 0 && removedCount === 0"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                        تأكيد الحفظ
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function permissionsMatrix() {
            return {
                confirmOpen: false,
                changed: false,
                addedCount: 0,
                removedCount: 0,

                trackChange() {
                    this.changed = true;
                },

                showConfirm() {
                    const form = document.getElementById('permissionsForm');
                    const currentChecks = form.querySelectorAll('input[type="checkbox"]');
                    let added = 0;
                    let removed = 0;

                    currentChecks.forEach(cb => {
                        const wasChecked = cb.defaultChecked;
                        const isNowChecked = cb.checked;

                        if (!wasChecked && isNowChecked) added++;
                        if (wasChecked && !isNowChecked) removed++;
                    });

                    this.addedCount = added;
                    this.removedCount = removed;
                    this.confirmOpen = true;
                },

                submitForm() {
                    this.confirmOpen = false;
                    document.getElementById('permissionsForm').submit();
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
