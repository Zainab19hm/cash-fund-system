<x-app-layout title="طلب جديد">
    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">طلب جديد</h1>
            <p class="mt-1 text-sm text-muted">إنشاء طلب صرف أو قبض جديد</p>
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
        <form method="POST" action="{{ route('client.orders.store') }}"
              class="rounded-xl border border-bdr bg-surface p-6 space-y-5"
              x-data="orderForm()">
            @csrf

            {{-- Type --}}
            <div>
                <label for="type" class="mb-1.5 block text-sm font-semibold text-text">نوع الطلب</label>
                <select name="type" id="type" required
                        x-model="orderType" @change="items.forEach(i => i.category_id = '')"
                        class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                    <option value="">اختر النوع...</option>
                    <option value="payment" {{ old('type') === 'payment' ? 'selected' : '' }}>صرف</option>
                    <option value="receipt" {{ old('type') === 'receipt' ? 'selected' : '' }}>قبض</option>
                </select>
                @error('type')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Amount --}}
            <div>
                <label for="amount" class="mb-1.5 block text-sm font-semibold text-text">المبلغ الإجمالي</label>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                       value="{{ old('amount') }}" required
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="0.00" />
                @error('amount')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Order Date --}}
            <div>
                <label for="order_date" class="mb-1.5 block text-sm font-semibold text-text">تاريخ الطلب</label>
                <input type="date" name="order_date" id="order_date"
                       value="{{ old('order_date', date('Y-m-d')) }}" required
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                @error('order_date')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="mb-1.5 block text-sm font-semibold text-text">الوصف</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                          placeholder="وصف مختصر للطلب...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Payer Name (receipt only) --}}
            <div x-show="orderType === 'receipt'" x-transition>
                <label for="payer_name" class="mb-1.5 block text-sm font-semibold text-text">اسم الدافع</label>
                <input type="text" name="payer_name" id="payer_name"
                       value="{{ old('payer_name') }}" required
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="اسم شخص أو جهة الدفع..." />
                @error('payer_name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="mb-1.5 block text-sm font-semibold text-text">ملاحظات</label>
                <textarea name="notes" id="notes" rows="2"
                          class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                          placeholder="ملاحظات إضافية...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Items --}}
            <div class="border-t border-bdr pt-5">
                <div class="mb-3 flex items-center justify-between">
                    <label class="text-sm font-semibold text-text">بنود الطلب</label>
                    <button type="button" @click="addItem()"
                            class="rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-primary/20">
                        + إضافة بند
                    </button>
                </div>

                @error('items')
                    <p class="mb-3 text-xs text-red-400">{{ $message }}</p>
                @enderror

                <template x-for="(item, index) in items" :key="index">
                    <div class="mb-4 rounded-xl border border-bdr bg-bg/50 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold text-muted" x-text="'بند #' + (index + 1)"></span>
                            <button type="button" @click="removeItem(index)"
                                    class="text-xs text-red-400 hover:text-red-300"
                                    x-show="items.length > 1">
                                حذف
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <select :name="'items[' + index + '][category_id]'" required
                                        x-model="item.category_id"
                                        class="w-full rounded-xl border border-bdr bg-surface px-3 py-2.5 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                                    <option value="">التصنيف...</option>
                                    <template x-for="cat in filteredCategories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                                @error('items.*.category_id')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input type="text" :name="'items[' + index + '][description]'"
                                       x-model="item.description" required maxlength="255"
                                       placeholder="وصف البند..."
                                       class="w-full rounded-xl border border-bdr bg-surface px-3 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                                @error('items.*.description')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <input type="number" :name="'items[' + index + '][amount]'"
                                       x-model.number="item.amount" step="0.01" min="0.01" required
                                       placeholder="المبلغ..."
                                       class="w-full rounded-xl border border-bdr bg-surface px-3 py-2.5 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20" />
                                @error('items.*.amount')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Items Total --}}
                <div class="mt-3 rounded-xl border border-primary/20 bg-primary/5 p-3 text-sm">
                    <span class="text-muted">مجموع البنود: </span>
                    <span class="font-bold text-primary" x-text="itemsTotal.toFixed(2)"></span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('client.orders.index') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    إلغاء
                </a>
                <button type="submit"
                        @click="return confirm('هل أنت متأكد من إنشاء هذا الطلب؟')"
                        class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    إنشاء الطلب
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function orderForm() {
            const allCategories = {!! json_encode($categories) !!};
            return {
                orderType: '{{ old("type") }}',
                items: {!! json_encode(old('items', [['category_id' => '', 'description' => '', 'amount' => '']])) !!},
                get itemsTotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
                },
                get filteredCategories() {
                    if (!this.orderType) return allCategories;
                    return allCategories.filter(c => c.type === this.orderType || c.type === 'both');
                },
                addItem() {
                    this.items.push({ category_id: '', description: '', amount: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
