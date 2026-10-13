@php($pharmaSections = [
    'hero' => ['Hero Pharma', 'Nội dung giới thiệu đầu trang và ảnh Hero.'],
    'categories' => ['Danh mục dược phẩm', 'Nhóm danh mục thuốc được công bố.'],
    'new_arrivals' => ['Sản phẩm mới cập nhật', 'Danh sách sản phẩm Pharma mới nhất.'],
])
<section class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="text-lg font-bold text-gray-900">Bố cục Homepage — Pharma</h2>
        <p class="mt-1 text-sm text-gray-500">Workspace độc lập. Không hiển thị Flash Sale, Top bán chạy hoặc các section bán lẻ của Default.</p>
    </div>
    <div class="space-y-3">
        @foreach($pharmaSectionOrder as $sectionKey)
            @if(isset($pharmaSections[$sectionKey]))
                <article wire:key="pharma-section-{{ $sectionKey }}" class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $pharmaSections[$sectionKey][0] }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $pharmaSections[$sectionKey][1] }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($sectionKey === 'categories')
                            <button type="button" wire:click="$toggle('editingPharmaCategories')" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Thiết lập</button>
                        @endif
                        <button type="button" wire:click="movePharmaSection('{{ $sectionKey }}', 'up')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Đưa lên">↑</button>
                        <button type="button" wire:click="movePharmaSection('{{ $sectionKey }}', 'down')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Đưa xuống">↓</button>
                        <label class="sr-only" for="pharma-visibility-{{ $sectionKey }}">Hiển thị {{ $pharmaSections[$sectionKey][0] }}</label>
                        <select id="pharma-visibility-{{ $sectionKey }}" wire:model="pharmaLayout.{{ $sectionKey }}" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                            <option value="all">Desktop + Mobile</option>
                            <option value="desktop">Chỉ Desktop</option>
                            <option value="mobile">Chỉ Mobile</option>
                            <option value="none">Ẩn hoàn toàn</option>
                        </select>
                    </div>
                </article>
            @endif
        @endforeach
    </div>
    @if($editingPharmaCategories)
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/40 p-5" wire:key="pharma-category-settings">
            <h3 class="text-base font-bold text-gray-900">Thiết lập UI danh mục dược phẩm</h3>
            <p class="mt-1 text-sm text-gray-600">Chỉ áp dụng cho Homepage Pharma; không thay đổi dữ liệu danh mục và template Default.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium text-gray-700">Nhãn section
                    <input type="text" wire:model="pharmaCategoryDisplay.eyebrow" maxlength="60" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                    @error('pharmaCategoryDisplay.eyebrow') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium text-gray-700">Tiêu đề
                    <input type="text" wire:model="pharmaCategoryDisplay.title" maxlength="120" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                    @error('pharmaCategoryDisplay.title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium text-gray-700 md:col-span-2">Mô tả (tùy chọn)
                    <textarea wire:model="pharmaCategoryDisplay.description" maxlength="300" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"></textarea>
                    @error('pharmaCategoryDisplay.description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium text-gray-700">Số danh mục
                    <select wire:model="pharmaCategoryDisplay.limit" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        @foreach([4, 8, 12, 16, 20, 24] as $count)
                            <option value="{{ $count }}">{{ $count }} danh mục</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700">Số cột Desktop
                    <select wire:model="pharmaCategoryDisplay.columns" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        <option value="2">2 cột</option><option value="3">3 cột</option><option value="4">4 cột</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700 md:col-span-2">Kiểu card
                    <select wire:model="pharmaCategoryDisplay.card_style" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        <option value="clinical">Clinical — có biểu tượng và mô tả điều hướng</option>
                        <option value="compact">Compact — card gọn, tập trung tên danh mục</option>
                    </select>
                </label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" wire:click="$set('editingPharmaCategories', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Đóng</button>
                <button type="button" wire:click="savePharmaCategories" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Lưu thiết lập danh mục</button>
            </div>
        </div>
    @endif
    <p class="mt-4 text-xs text-gray-500">Cấu hình Pharma được lưu riêng. Thay đổi hiển thị sau khi lưu và tải lại Website.</p>
</section>
