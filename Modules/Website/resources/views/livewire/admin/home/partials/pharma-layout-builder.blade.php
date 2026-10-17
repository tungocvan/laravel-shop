@php
$pharmaSections = [
    'hero' => ['Hero Pharma', 'Nội dung giới thiệu đầu trang và ảnh Hero.'],
    'categories' => ['Danh mục dược phẩm', 'Nhóm danh mục thuốc được công bố.'],
    'new_arrivals' => ['Sản phẩm mới cập nhật', 'Danh sách sản phẩm Pharma mới nhất.'],
];
foreach($pharmaBlogSections as $key => $config) {
    $pharmaSections[$key] = [$config['title'] ?? 'Tin tức / Blog', 'Bài viết công khai theo chuyên mục và bộ lọc.'];
}
foreach($pharmaProductSections as $key => $config) {
    $pharmaSections[$key] = [$config['title'] ?? 'Section sản phẩm', 'Danh sách sản phẩm Pharma theo cấu hình riêng.'];
}
@endphp
<style>
.pharma-category-card-preview{display:flex;align-items:center;gap:var(--category-card-gap);min-height:var(--category-card-height);padding:var(--category-card-padding);border:1px solid var(--category-card-border);border-radius:var(--category-card-radius);background:var(--category-card-bg);color:var(--category-card-text);font-size:var(--category-card-font);flex-wrap:wrap}.pharma-category-card-preview .preview-icon{display:flex;align-items:center;justify-content:center;width:48px;height:48px;flex-shrink:0;border-radius:12px;background:#fff;color:var(--category-card-accent);border:1px solid var(--category-card-border)}.pharma-category-card-preview .preview-count{background:#fff;color:var(--category-card-accent);white-space:nowrap}.pharma-category-card-preview .preview-arrow{color:var(--category-card-accent)}

.pharma-category-v2-preview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
@media(max-width:639px){.pharma-category-v2-preview-grid{grid-template-columns:minmax(0,1fr)}.pharma-category-v2-preview-grid[data-preview-columns="2"]{grid-template-columns:repeat(2,minmax(0,1fr))}.pharma-category-v2-preview-grid[data-preview-columns="2"] .pharma-category-card-custom{flex-wrap:wrap}.pharma-category-v2-preview-grid[data-preview-columns="2"] .pharma-category-card-content{order:3;flex-basis:100%}}
@media(min-width:640px) and (max-width:1023px){.pharma-category-v2-preview-grid[data-preview-tablet="1"]{grid-template-columns:1fr}.pharma-category-v2-preview-grid[data-preview-tablet="3"]{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(min-width:1024px){.pharma-category-v2-preview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@include('Website::partials.pharma-category-card-style')
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
                        @if(isset($pharmaBlogSections[$sectionKey]))
                            <button type="button" wire:click="$set('editingPharmaBlogSection', '{{ $sectionKey }}')" class="rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm">Thiết lập</button>
                            <button type="button" wire:click="duplicatePharmaBlogSection('{{ $sectionKey }}')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">Nhân đôi</button>
                            @if($sectionKey !== 'blog')
                                <button type="button" wire:click="removePharmaBlogSection('{{ $sectionKey }}')" wire:confirm="Xóa section Blog này?" class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700">Xóa</button>
                            @endif
                        @endif
                        @if($sectionKey === 'categories')
                            <button type="button" wire:click="$set('editingPharmaCategories', true)" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Thiết lập</button>
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
    @if(auth('admin')->user()?->hasPermissionTo('website.settings.manage', 'admin'))
        <article class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/30 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-900">Đặt hàng nhanh Pharma — nguồn bảng giá</h3>
                    <p class="mt-1 text-sm text-gray-500">Bật/tắt đặt hàng nhanh và chọn bảng giá từ Pharma. Cấu hình được lưu riêng, không ảnh hưởng template Default.</p>
                </div>
                <a href="{{ route('admin.website.pharma-quick-order.index') }}" class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 active:scale-[0.985]">Thiết lập</a>
            </div>
        </article>
    @endif
    <p class="mt-4 text-xs text-gray-500">Cấu hình Pharma được lưu riêng. Thay đổi hiển thị sau khi lưu và tải lại Website.</p>

    @if($editingPharmaCategories || $editingPharmaProductSection || $editingPharmaBlogSection)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-0 sm:p-4" role="presentation">
            <section role="dialog" aria-modal="true" aria-label="Thiết lập section Homepage Pharma" class="flex h-[100dvh] w-full max-w-6xl flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[90dvh] sm:rounded-2xl">
                <header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div><h2 class="text-lg font-bold text-gray-900">Thiết lập Section Homepage Pharma</h2><p class="text-xs text-gray-500">Cấu hình riêng cho Website Pharma</p></div>
                    <button type="button" wire:click="closePharmaSectionEditor" aria-label="Đóng cửa sổ thiết lập" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">✕</button>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
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
                <label class="text-sm font-medium">Kiểu hiển thị
                    <select wire:model="pharmaProductSections.{{ $sectionKey }}.display_mode" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        <option value="load_more">Xem thêm (mặc định)</option>
                        <option value="fixed">Hiển thị cố định</option>
                        <option value="pagination">Phân trang</option>
                    </select>
                </label>
                <p class="self-center text-xs text-gray-600">Số sản phẩm là số hiển thị ban đầu hoặc mỗi lần tải. Các section nhân đôi có cấu hình riêng.</p>
                <div class="md:col-span-2">
                    <p class="text-sm font-semibold">Danh mục dược phẩm</p>
                    <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="pharmaOnlyCategoriesWithProducts" class="rounded border-gray-300">
                        Chỉ danh mục có sản phẩm
                    </label>
                    <div class="mt-2 grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-2">
                        @foreach($pharmaMedicineCategories as $category)
                            @if(!$pharmaOnlyCategoriesWithProducts || $category->storefront_products_count > 0 || in_array((int) $category->id, array_map('intval', (array) ($pharmaProductSections[$sectionKey]['category_ids'] ?? [])), true))
                                <label wire:key="pharma-product-category-{{ $sectionKey }}-{{ $category->id }}" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <input type="checkbox" value="{{ $category->id }}" wire:model="pharmaProductSections.{{ $sectionKey }}.category_ids" class="rounded border-gray-300">
                                    <span class="flex-1">{{ $category->name }}</span>
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs {{ $category->storefront_products_count ? 'text-emerald-700' : 'text-gray-400' }}">{{ $category->storefront_products_count }} SP</span>
                                </label>
                            @endif
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
    @if($editingPharmaBlogSection && isset($pharmaBlogSections[$editingPharmaBlogSection]))
        @php($blogKey = $editingPharmaBlogSection)
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/40 p-5" wire:key="blog-section-editor-{{ $blogKey }}">
            <h3 class="font-bold text-gray-900">Thiết lập Tin tức / Blog</h3>
            <p class="mt-1 text-sm text-gray-600">Chọn nhiều chuyên mục bài viết. Không chọn = tất cả bài viết đã xuất bản.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-medium">Nhãn nhỏ<input type="text" maxlength="60" wire:model="pharmaBlogSections.{{ $blogKey }}.eyebrow" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"></label>
                <label class="text-sm font-medium">Tiêu đề<input type="text" maxlength="120" wire:model="pharmaBlogSections.{{ $blogKey }}.title" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"></label>
                <label class="text-sm font-medium md:col-span-2">Mô tả<textarea rows="2" maxlength="300" wire:model="pharmaBlogSections.{{ $blogKey }}.description" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"></textarea></label>
                <label class="text-sm font-medium">Sắp xếp<select wire:model="pharmaBlogSections.{{ $blogKey }}.sort" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"><option value="newest">Mới nhất</option><option value="oldest">Cũ nhất</option><option value="name_asc">Tên A–Z</option><option value="name_desc">Tên Z–A</option></select></label>
                <label class="text-sm font-medium">Thời gian đăng<select wire:model="pharmaBlogSections.{{ $blogKey }}.period" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"><option value="all">Tất cả</option><option value="7">7 ngày</option><option value="30">30 ngày</option><option value="90">90 ngày</option></select></label>
                <label class="text-sm font-medium">Số bài viết<select wire:model="pharmaBlogSections.{{ $blogKey }}.limit" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">@foreach([4,8,12,16] as $count)<option value="{{ $count }}">{{ $count }}</option>@endforeach</select></label>
                <label class="text-sm font-medium">Kiểu card<select wire:model="pharmaBlogSections.{{ $blogKey }}.card_style" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"><option value="grid">Card lưới</option><option value="list">Danh sách</option></select></label>
                <label class="text-sm font-medium">Hiển thị<select wire:model="pharmaBlogSections.{{ $blogKey }}.display_mode" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2"><option value="load_more">Xem thêm</option><option value="fixed">Cố định</option></select></label>
                <div class="md:col-span-2"><p class="text-sm font-semibold">Chuyên mục bài viết</p><div class="mt-2 grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-2">@foreach($pharmaBlogCategories as $category)<label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm"><input type="checkbox" value="{{ $category->id }}" wire:model="pharmaBlogSections.{{ $blogKey }}.category_ids" class="rounded border-gray-300"><span class="flex-1">{{ $category->name }}</span><span class="rounded-full border border-gray-200 px-2 py-0.5 text-xs">{{ $category->published_posts_count }} bài</span></label>@endforeach</div></div>
            </div>
            @error('pharmaBlogSections') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end gap-2"><button type="button" wire:click="$set('editingPharmaBlogSection', null)" class="rounded-lg border border-gray-300 bg-white px-4 py-2">Đóng</button><button type="button" wire:click="savePharmaBlogSections" class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white">Lưu Section Blog</button></div>
        </div>
    @endif
    @if($editingPharmaCategories)
        <div class="space-y-5" wire:key="pharma-category-settings">
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
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-3">
                <div class="text-sm font-semibold text-gray-800">Chọn danh mục hiển thị</div>
                <p class="mt-1 text-xs text-gray-500">Chỉ danh mục dược phẩm đang hoạt động. Không chọn = tự động hiển thị theo thứ tự mặc định.</p>
                <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="pharmaOnlyCategoriesWithProducts" class="rounded border-gray-300">
                    Chỉ danh mục có sản phẩm
                </label>
                <div class="mt-3 grid max-h-52 gap-2 overflow-y-auto md:grid-cols-2">
                    @foreach($pharmaMedicineCategories as $category)
                        @if(!$pharmaOnlyCategoriesWithProducts || $category->storefront_products_count > 0 || in_array((int) $category->id, array_map('intval', (array) ($pharmaCategoryDisplay['selected_ids'] ?? [])), true))
                        <label wire:key="pharma-category-choice-{{ $category->id }}" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <input type="checkbox" value="{{ $category->id }}" wire:model.live="pharmaCategoryDisplay.selected_ids" class="rounded border-gray-300">
                            <span class="flex-1">{{ $category->name }}</span>
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs {{ $category->storefront_products_count ? 'text-emerald-700' : 'text-gray-400' }}">{{ $category->storefront_products_count }} SP</span>
                        </label>
                        @endif
                    @endforeach
                </div>
                @if(!empty($pharmaCategoryDisplay['selected_ids']))
                    <div class="mt-3 space-y-1">
                        <div class="text-xs font-semibold text-gray-600">Thứ tự danh mục đã chọn</div>
                        @foreach($pharmaCategoryDisplay['selected_ids'] as $position => $categoryId)
                            @php($chosenCategory = $pharmaMedicineCategories->firstWhere('id', (int) $categoryId))
                            @if($chosenCategory)
                                <div wire:key="pharma-category-order-{{ $categoryId }}" class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-1.5 text-sm">
                                    <span>{{ $position + 1 }}. {{ $chosenCategory->name }}</span>
                                    <div class="flex gap-1">
                                        <button type="button" wire:click="movePharmaCategory('{{ $categoryId }}', 'up')" class="rounded border border-gray-300 px-2 py-1" aria-label="Đưa danh mục lên">↑</button>
                                        <button type="button" wire:click="movePharmaCategory('{{ $categoryId }}', 'down')" class="rounded border border-gray-300 px-2 py-1" aria-label="Đưa danh mục xuống">↓</button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
                @error('pharmaCategoryDisplay.selected_ids') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="mt-5 rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><h4 class="font-semibold text-gray-900">Giao diện Card danh mục</h4><p class="text-xs text-gray-500">Không bật tùy chỉnh: giữ nguyên giao diện cũ.</p></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="pharmaCategoryCardEnabled" class="rounded border-gray-300"> Tùy chỉnh Card</label>
                </div>
                @if($pharmaCategoryCardEnabled)
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="text-sm font-medium">Preset
                        <select wire:model.live="pharmaCategoryCard.preset" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                            @foreach(['clean'=>'Pharma Clean (đề xuất)','premium'=>'Pharma Premium','soft'=>'Pharma Soft','clinical'=>'Classic (cũ)','compact'=>'Compact (cũ)','minimal'=>'Minimal'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Bố cục Card
                        <select wire:model.live="pharmaCategoryCard.layout" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                            <option value="horizontal">Ngang · Icon / Tên / Mũi tên</option><option value="stacked">Hai tầng · Icon trên / Nội dung dưới</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium">Hiển thị số sản phẩm
                        <select wire:model.live="pharmaCategoryCard.count_style" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                            <option value="subtitle">Dòng mô tả dưới tên</option><option value="badge">Badge</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium">Kích thước icon (px)
                        <input type="number" min="36" max="64" wire:model.live.debounce.300ms="pharmaCategoryCard.icon_size" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                    </label>
                    @foreach(['font_size'=>['Font size',11,20],'radius'=>['Bo góc',0,32],'padding'=>['Padding',8,32],'min_height'=>['Chiều cao tối thiểu',80,180],'gap'=>['Khoảng cách',0,32],'mobile_columns'=>['Số cột Mobile',1,2],'tablet_columns'=>['Số cột Tablet',1,3]] as $key=>$field)
                        <label class="text-sm font-medium">{{ $field[0] }} ({{ $key === 'mobile_columns' || $key === 'tablet_columns' ? 'cột' : 'px' }})
                            <input type="number" min="{{ $field[1] }}" max="{{ $field[2] }}" wire:model.live.debounce.300ms="pharmaCategoryCard.{{ $key }}" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        </label>
                    @endforeach
                    @foreach(['accent'=>'Màu chủ đạo','background'=>'Nền Card','text_color'=>'Màu chữ','border_color'=>'Màu viền','icon_color'=>'Màu icon','icon_background'=>'Nền icon'] as $key=>$label)
                        <label class="text-sm font-medium">{{ $label }}<input type="color" wire:model.live="pharmaCategoryCard.{{ $key }}" class="mt-1 block h-10 w-full rounded-lg border border-gray-300 bg-white p-1"></label>
                    @endforeach
                </div>
                <label class="mt-4 block max-w-sm text-sm font-medium">Biểu tượng Card
                    <select wire:model.live="pharmaCategoryCard.icon" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                        @foreach(['medical'=>'Y tế / Dược phẩm','heart'=>'Trái tim','shield'=>'Bảo vệ','pill'=>'Viên thuốc','cross'=>'Dấu cộng','leaf'=>'Lá cây','none'=>'Không có icon'] as $value=>$label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <h5 class="text-sm font-semibold text-gray-900">Icon riêng theo danh mục</h5>
                    <p class="mt-1 text-xs text-gray-500">Chọn danh mục và icon tương ứng. Những danh mục khác giữ nguyên thiết lập đã lưu.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-medium text-gray-700">Danh mục
                            <select wire:model.live="selectedCategoryIconId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                                <option value="">Chọn danh mục</option>
                                @foreach($pharmaCategoryIconChoices as $categoryId => $categoryName)
                                    <option value="{{ $categoryId }}">{{ $categoryName }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-medium text-gray-700">Icon của danh mục
                            <select wire:model.live="selectedCategoryIconValue" wire:key="category-icon-picker-{{ $selectedCategoryIconId ?: 'none' }}" @disabled(!$selectedCategoryIconId) class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                                <option value="">Dùng icon chung</option>
                                @foreach(['medical'=>'Y tế','heart'=>'Trái tim','shield'=>'Bảo vệ','pill'=>'Viên thuốc','cross'=>'Dấu cộng','leaf'=>'Lá cây','none'=>'Ẩn icon'] as $value=>$label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="pharmaCategoryCard.icon_shadow" class="rounded border-gray-300"> Shadow nhẹ cho icon</label>
                    @foreach(['show_icon'=>'Hiện icon','show_count'=>'Hiện số SP','show_arrow'=>'Hiện mũi tên'] as $key=>$label)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="pharmaCategoryCard.{{ $key }}" class="rounded border-gray-300"> {{ $label }}</label>
                    @endforeach
                </div>
                @php($preview = app(\Modules\Website\Services\PharmaCategoryCardStyle::class)->normalize($pharmaCategoryCard))
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase text-gray-500">Xem trước · Lưới Card danh mục</p>
                        <span class="text-xs text-gray-500">4 danh mục thực tế · Số SP minh họa · Chung CSS với Website</span>
                    </div>
                    <div class="pharma-category-v2-preview-grid" data-preview-columns="{{ $preview['mobile_columns'] }}" data-preview-tablet="{{ $preview['tablet_columns'] }}">
                        @php
                            $previewCategoryIds = array_keys($pharmaCategoryIconChoices);
                            if ($selectedCategoryIconId !== '' && array_key_exists((int) $selectedCategoryIconId, $pharmaCategoryIconChoices)) {
                                $previewCategoryIds = array_values(array_unique(array_merge([(int) $selectedCategoryIconId], $previewCategoryIds)));
                            }
                            $previewCategoryIds = array_slice($previewCategoryIds, 0, 4);
                        @endphp
                        @foreach($previewCategoryIds as $previewIndex => $previewCategoryId)
                            @include('Website::partials.pharma-category-card-v2', [
                                'cardName' => $pharmaCategoryIconChoices[$previewCategoryId],
                                'cardCount' => [1, 3, 6, 2][$previewIndex],
                                'cardDesign' => $preview,
                                'cardIcon' => $preview['icons_by_category'][$previewCategoryId] ?? $preview['icon'],
                                'cardCss' => app(\Modules\Website\Services\PharmaCategoryCardStyle::class)->css($preview),
                                'cardPreview' => true,
                            ])
                        @endforeach
                    </div>
                </div>
                @endif
                <button type="button" wire:click="resetPharmaCategoryCard" class="mt-3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">Khôi phục Card mặc định</button>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" wire:click="$set('editingPharmaCategories', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Đóng</button>
                <button type="button" wire:click="savePharmaCategories" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Lưu thiết lập danh mục</button>
            </div>
        </div>
    @endif
                </div>
            </section>
        </div>
</section>
