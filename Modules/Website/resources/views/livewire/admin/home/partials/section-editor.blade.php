@php
    $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
    $checkClass = 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500';
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <button type="button" wire:click="$set('activeTab', 'layout')" class="mb-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Quay lại bố cục</button>
            <h2 class="text-lg font-bold text-gray-900">
                @switch($activeTab)
                    @case('categories') Danh mục nổi bật @break
                    @case('featured') Sản phẩm nổi bật @break
                    @case('auto_query') Auto Query @break
                    @case('promo_banner') Banner Quảng cáo @break
                    @case('newsletter') Newsletter @break
                    @case('trust_badges') Cam kết / Icon service @break
                @endswitch
            </h2>
            <p class="mt-1 text-sm text-gray-500">Chỉ hiển thị cấu hình của section đang quản trị để giao diện gọn và dễ tập trung.</p>
        </div>
    </div>

    @if($activeTab === 'categories')
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($allCategories as $cat)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition {{ in_array($cat->id, $data['category_ids']) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                    <input type="checkbox" value="{{ $cat->id }}" wire:model="data.category_ids" class="{{ $checkClass }}">
                    <span class="text-sm font-medium text-gray-900">{{ $cat->name }}</span>
                </label>
            @empty
                <div class="sm:col-span-2 lg:col-span-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-sm text-gray-500">Chưa có danh mục khả dụng.</div>
            @endforelse
        </div>
    @endif

    @if($activeTab === 'featured')
        <div class="mb-4 flex items-center justify-between gap-3"><p class="text-sm text-gray-500">Đã chọn {{ count($data['featured_ids']) }} sản phẩm.</p><button type="button" wire:click="openProductPicker" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">+ Chọn sản phẩm</button></div>
        @forelse($selectedProducts as $product)
            <div class="mb-2 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                <img src="{{ $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image)) : 'https://placehold.co/80' }}" alt="{{ $product->title }}" class="h-12 w-12 rounded-lg object-cover">
                <div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold text-gray-900">{{ $product->title }}</div><div class="text-xs text-gray-500">ID: {{ $product->id }}</div></div>
                <button type="button" wire:click="toggleProduct({{ $product->id }})" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600">Xóa</button>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-sm text-gray-500">Chưa chọn sản phẩm nổi bật.</div>
        @endforelse
    @endif

    @if($activeTab === 'auto_query')
        <div class="grid gap-5 md:grid-cols-3">
            <div><label class="block text-sm font-semibold text-gray-700">Hàng mới về</label><input type="number" min="1" max="50" wire:model="newArrivalsCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Theo ngày tạo mới nhất.</p></div>
            <div><label class="block text-sm font-semibold text-gray-700">Top bán chạy</label><input type="number" min="1" max="50" wire:model="bestSellersCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Theo sold_count.</p></div>
            <div><label class="block text-sm font-semibold text-gray-700">Tin tức mới nhất</label><input type="number" min="1" max="10" wire:model="blogCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Số bài viết mới nhất.</p></div>
        </div>
    @endif

    @if($activeTab === 'promo_banner')
        <div class="mb-5 flex justify-end"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" wire:model="promoBanner.show" class="{{ $checkClass }}"> Hiển thị</label></div>
        <div class="grid gap-5 md:grid-cols-2">
            <div class="space-y-4"><x-image-upload wire:model="newPromoImage" :newImage="$newPromoImage" :oldImage="$promoBanner['image']" label="Banner Quảng Cáo" /><div><label class="block text-sm font-semibold text-gray-700">Image URL dự phòng</label><input type="text" wire:model="promoBanner.image" class="{{ $fieldClass }}"></div></div>
            <div class="space-y-4"><div><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="promoBanner.title" class="{{ $fieldClass }}"></div><div><label class="block text-sm font-semibold text-gray-700">Mô tả</label><textarea wire:model="promoBanner.sub_title" rows="3" class="{{ $fieldClass }}"></textarea></div><div class="grid gap-4 sm:grid-cols-2"><div><label class="block text-sm font-semibold text-gray-700">Nhãn nút</label><input type="text" wire:model="promoBanner.btn_text" class="{{ $fieldClass }}"></div><div><label class="block text-sm font-semibold text-gray-700">URL nút</label><input type="text" wire:model="promoBanner.link" class="{{ $fieldClass }}"></div></div><div><label class="block text-sm font-semibold text-gray-700">URL xem chi tiết</label><input type="text" wire:model="promoBanner.details_link" class="{{ $fieldClass }}"></div></div>
        </div>
    @endif

    @if($activeTab === 'newsletter')
        <div class="mb-5 flex justify-end"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" wire:model="newsletter.show" class="{{ $checkClass }}"> Hiển thị</label></div>
        <div class="grid gap-5 md:grid-cols-2"><div><label class="block text-sm font-semibold text-gray-700">Badge</label><input type="text" wire:model="newsletter.badge" class="{{ $fieldClass }}"></div><div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="newsletter.title" class="{{ $fieldClass }}"></div><div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Mô tả</label><textarea wire:model="newsletter.description" rows="3" class="{{ $fieldClass }}"></textarea></div></div>
    @endif

    @if($activeTab === 'trust_badges')
        <div class="mb-4 flex justify-end"><button type="button" wire:click="addBadge" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">+ Thêm cam kết</button></div>
        <div class="space-y-3">
            @forelse($data['trust_badges'] as $index => $badge)
                <div wire:key="homepage-trust-badge-{{ $index }}" class="grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-4"><label class="block text-sm font-semibold text-gray-700">Icon / Image URL</label><input type="text" wire:model.live="data.trust_badges.{{ $index }}.icon" class="{{ $fieldClass }}"></div>
                    <div class="md:col-span-3"><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="data.trust_badges.{{ $index }}.title" class="{{ $fieldClass }}"></div>
                    <div class="md:col-span-4"><label class="block text-sm font-semibold text-gray-700">Mô tả phụ</label><input type="text" wire:model="data.trust_badges.{{ $index }}.sub_title" class="{{ $fieldClass }}"></div>
                    <button type="button" wire:click="removeBadge({{ $index }})" class="rounded-lg border border-red-200 bg-white px-3 py-2.5 text-sm font-semibold text-red-600 md:col-span-1">Xóa</button>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">Chưa có cam kết.</div>
            @endforelse
        </div>
    @endif
</section>

@if($showProductPicker)
    <div class="fixed inset-0 z-[999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60" wire:click="$set('showProductPicker', false)"></div>
        <div class="relative flex min-h-full items-center justify-center p-4"><div class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl"><div class="border-b border-gray-200 p-5"><h3 class="font-bold text-gray-900">Chọn sản phẩm nổi bật</h3><input type="text" wire:model.live.debounce.300ms="productSearchQuery" placeholder="Tìm theo tên sản phẩm..." class="{{ $fieldClass }}"></div><div class="max-h-[55vh] space-y-2 overflow-y-auto bg-gray-50 p-5">@forelse($searchProducts as $product) @php($selected = in_array($product->id, $data['featured_ids'])) <button type="button" wire:click="toggleProduct({{ $product->id }})" class="flex w-full items-center gap-3 rounded-lg border p-3 text-left {{ $selected ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white' }}"><div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold text-gray-900">{{ $product->title }}</div><div class="text-xs text-gray-500">{{ number_format($product->regular_price) }}đ</div></div><span class="text-xs font-semibold">{{ $selected ? 'Đã chọn' : 'Chọn' }}</span></button> @empty <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">Không có sản phẩm phù hợp.</div> @endforelse</div><div class="flex justify-end border-t border-gray-200 p-4"><button type="button" wire:click="$set('showProductPicker', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Đóng</button></div></div></div>
    </div>
@endif
