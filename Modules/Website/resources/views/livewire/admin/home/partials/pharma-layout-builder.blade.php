@php
$pharmaSections = [
    'hero' => ['Hero Pharma', 'Nội dung giới thiệu đầu trang và ảnh Hero.'],
    'categories' => ['Danh mục dược phẩm', 'Nhóm danh mục thuốc được công bố.'],
    'new_arrivals' => ['Sản phẩm mới cập nhật', 'Danh sách sản phẩm Pharma mới nhất.'],
];
foreach($pharmaProductSections as $key => $config) {
    $pharmaSections[$key] = [$config['title'] ?? 'Section sản phẩm', 'Danh sách sản phẩm Pharma theo cấu hình riêng.'];
}
@endphp
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
                        @if(isset($pharmaProductSections[$sectionKey]))
                            <button type="button" wire:click="$set('editingPharmaProductSection', '{{ $sectionKey }}')" class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm">Thiết lập</button>
                            <button type="button" wire:click="duplicatePharmaProductSection('{{ $sectionKey }}')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">Nhân đôi</button>
                            @if($sectionKey !== 'new_arrivals')
                                <button type="button" wire:click="removePharmaProductSection('{{ $sectionKey }}')" wire:confirm="Xóa section này?" class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700">Xóa</button>
                            @endif
                        @endif
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

    @if($editingPharmaProductSection && isset($pharmaProductSections[$editingPharmaProductSection]))
        @php($sectionKey = $editingPharmaProductSection)
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/40 p-5" wire:key="product-section-editor-{{ $sectionKey }}">
            <h3 class="font-bold text-gray-900">Thiết lập Section sản phẩm</h3>
            <p class="mt-1 text-sm text-gray-600">Chọn nhiều danh mục bằng checkbox. Không chọn danh mục nghĩa là lấy tất cả sản phẩm Pharma.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-medium">Nhãn nhỏ
                    <input type="text" maxlength="60" wire:model="pharmaProductSections.{{ $sectionKey }}.eyebrow" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                </label>
                <label class="text-sm font-medium">Tiêu đề
                    <input type="text" maxlength="120" wire:model="pharmaProductSections.{{ $sectionKey }}.title" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                </label>
                <label class="text-sm font-medium">Sắp xếp
                    <select wire:model="pharmaProductSections.{{ $sectionKey }}.sort" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        <option value="newest">Mới cập nhật</option><option value="created">Mới tạo</option><option value="name_asc">Tên A–Z</option><option value="name_desc">Tên Z–A</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Thời gian cập nhật
                    <select wire:model="pharmaProductSections.{{ $sectionKey }}.period" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        <option value="all">Tất cả</option><option value="7">7 ngày</option><option value="30">30 ngày</option><option value="90">90 ngày</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Số sản phẩm
                    <select wire:model="pharmaProductSections.{{ $sectionKey }}.limit" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        @foreach([4,8,12,16,20,24] as $count)<option value="{{ $count }}">{{ $count }}</option>@endforeach
                    </select>
                </label>
                <div class="md:col-span-2">
                    <p class="text-sm font-semibold">Danh mục dược phẩm</p>
                    <div class="mt-2 grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-2">
                        @foreach($pharmaMedicineCategories as $category)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $category->id }}" wire:model="pharmaProductSections.{{ $sectionKey }}.category_ids" class="rounded border-gray-300"> {{ $category->name }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
            @error('pharmaProductSections') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" wire:click="$set('editingPharmaProductSection', null)" class="rounded-lg border border-gray-300 px-4 py-2">Đóng</button>
                <button type="button" wire:click="savePharmaProductSections" class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white">Lưu Section sản phẩm</button>
            </div>
        </div>
    @endif
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
    <article class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/30 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-900">Trang danh mục sản phẩm — /product</h3>
                <p class="mt-1 text-sm text-gray-500">Thiết lập bộ lọc tra cứu trên trang danh mục Pharma; không ảnh hưởng template Default.</p>
            </div>
            <button type="button" wire:click="$toggle('editingPharmaProductFilters')" class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Thiết lập</button>
        </div>
        @if($editingPharmaProductFilters)
            <div class="mt-4 space-y-3 border-t border-emerald-100 pt-4" wire:key="pharma-product-filters">
                <p class="text-sm font-semibold text-gray-700">Ẩn / hiện bộ lọc tra cứu</p>
                @foreach(['category' => 'Nhóm dược phẩm', 'supplier' => 'Nhà cung cấp', 'product_type' => 'Loại sản phẩm', 'circular_group' => 'Nhóm thuốc theo thông tư'] as $key => $label)
                    <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800">
                        <span>{{ $label }}</span>
                        <input type="checkbox" wire:model="pharmaProductFilters.{{ $key }}" class="h-5 w-5 rounded border-gray-300 text-emerald-700">
                    </label>
                @endforeach
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('product.list') }}" target="_blank" class="mr-auto text-sm font-semibold text-emerald-700">Xem trang sản phẩm ↗</a>
                    <button type="button" wire:click="$set('editingPharmaProductFilters', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Đóng</button>
                    <button type="button" wire:click="savePharmaProductFilters" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Lưu bộ lọc</button>
                </div>
            </div>
        @endif
    </article>
    <p class="mt-4 text-xs text-gray-500">Cấu hình Pharma được lưu riêng. Thay đổi hiển thị sau khi lưu và tải lại Website.</p>
</section>
