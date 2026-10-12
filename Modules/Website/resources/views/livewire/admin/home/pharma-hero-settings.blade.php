<div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Hero Trang chủ Pharma</h2>
            <p class="mt-1 text-sm text-gray-500">Nội dung riêng cho giao diện Pharma. Không ảnh hưởng template mặc định.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-emerald-700 hover:underline">Xem Website ↗</a>
    </div>
    <form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-semibold text-gray-700">Nhãn Hero
                <input type="text" wire:model="hero.eyebrow" maxlength="80" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
                @error('hero.eyebrow') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-semibold text-gray-700">Nút hành động
                <input type="text" wire:model="hero.button" maxlength="60" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
                @error('hero.button') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
        <label class="block text-sm font-semibold text-gray-700">Tiêu đề
            <input type="text" wire:model="hero.title" maxlength="180" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15">
            @error('hero.title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block text-sm font-semibold text-gray-700">Mô tả
            <textarea wire:model="hero.description" rows="3" maxlength="500" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15"></textarea>
            @error('hero.description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block text-sm font-semibold text-gray-700">Ảnh Hero (tùy chọn, tối đa 3MB)
            <input type="file" wire:model="heroImage" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-800 hover:border-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
            @error('heroImage') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        @if($heroImage)
            <img src="{{ $heroImage->temporaryUrl() }}" alt="Xem trước ảnh đang chọn" class="max-h-40 rounded-lg object-contain">
        @elseif(!empty($hero['image']))
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($hero['image']) }}" alt="Ảnh Hero hiện tại" class="max-h-40 rounded-lg object-contain">
        @endif
        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save,heroImage" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">Lưu Hero Pharma</button>
        </div>
    </form>
</div>
