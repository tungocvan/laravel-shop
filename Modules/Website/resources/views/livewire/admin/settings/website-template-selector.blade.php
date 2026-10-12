<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
        <h2 class="text-lg font-bold text-slate-900">Template Website</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn giao diện công khai. Giao diện mặc định được giữ nguyên để có thể quay lại khi cần.</p>
    </div>
    <form wire:submit="save" class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <label class="block flex-1 text-sm font-semibold text-slate-700" for="website-template">
            Giao diện đang chọn
            <select id="website-template" wire:model="template" class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="default">Website mặc định</option>
                <option value="pharma">Pharma — giao diện dược phẩm (đang xây dựng)</option>
            </select>
            @error('template')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
            Lưu template
        </button>
    </form>
    <p class="mt-3 text-xs text-amber-700">Pharma Template hiện mới có trang chủ nền tảng; header, danh mục và chi tiết sản phẩm sẽ được hoàn thiện trong các checkpoint tiếp theo. Không nên kích hoạt trên production.</p>
</section>
