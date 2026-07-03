<x-app-layout title="تعديل التصنيف">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="font-heading text-2xl font-bold text-primary">تعديل التصنيف</h1>
            <p class="mt-1 text-sm text-muted">تعديل بيانات التصنيف: {{ $category->name }}</p>
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
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="rounded-xl border border-bdr bg-surface p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-text">اسم التصنيف</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required maxlength="100"
                       class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text placeholder-muted transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
                       placeholder="أدخل اسم التصنيف" />
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type --}}
            <div>
                <label for="type" class="mb-1.5 block text-sm font-semibold text-text">النوع</label>
                <select name="type" id="type" required
                        class="w-full rounded-xl border border-bdr bg-bg px-4 py-3 text-sm text-text transition-colors focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                    <option value="payment" {{ old('type', $category->type) === 'payment' ? 'selected' : '' }}>صرف</option>
                    <option value="receipt" {{ old('type', $category->type) === 'receipt' ? 'selected' : '' }}>قبض</option>
                    <option value="both" {{ old('type', $category->type) === 'both' ? 'selected' : '' }}>صرف وقبض</option>
                </select>
                @error('type')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.categories.index') }}"
                   class="rounded-xl border border-bdr bg-surface px-5 py-2.5 text-sm font-semibold text-text transition-colors hover:bg-bg">
                    إلغاء
                </a>
                <button type="submit"
                        class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:brightness-110 active:scale-[0.98]">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
