<div class="max-w-6xl mx-auto pb-24">
    @php
        $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
        $compactFieldClass = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
        $checkClass = 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500';
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quản trị Trang Chủ</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý bố cục và nội dung theo từng Homepage Section.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">Xem frontend ↗</a>
    </div>

    @error('builder')
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="mb-6 overflow-x-auto border-b border-gray-200">
        <nav class="flex min-w-max gap-6" aria-label="Homepage settings tabs">
            @foreach (['layout' => 'Bố cục & Hiển thị', 'data' => 'Dữ liệu & Nội dung', 'trust_badges' => 'Cam kết / Icon service'] as $key => $label)
                <button type="button" wire:click="setTab('{{ $key }}')" class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-semibold transition {{ $activeTab === $key ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' }}">{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    @if($activeTab === 'layout')
        <div class="space-y-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                Kéo để đổi thứ tự. Nhân bản, ẩn, khôi phục và reorder chỉ thay Builder state; bấm <strong>Lưu thay đổi</strong> mới publish.
            </div>

            <div class="grid grid-cols-1 gap-4" x-data x-init="Sortable.create($el, { handle: '.section-drag-handle', animation: 160, ghostClass: 'opacity-40', onEnd() { $wire.reorderSections(this.toArray()) } })">
                @foreach($sectionCards as $card)
                    @php($key = $card['layout_key'])
                    @php($state = $layout[$key] ?? 'all')
                    <article data-id="{{ $key }}" wire:key="homepage-section-card-{{ $key }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-300">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <button type="button" title="Kéo để thay đổi vị trí" class="section-drag-handle mt-0.5 shrink-0 cursor-grab rounded-lg border border-indigo-100 bg-indigo-50 p-2 text-indigo-600 active:cursor-grabbing">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                </button>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-gray-900">{{ $card['label'] }}</h3>
                                        @if($card['is_copy'])<span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">Bản sao</span>@endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ $card['description'] }}</p>
                                    <div class="mt-2 text-xs text-gray-400">Key: <code>{{ $card['section_key'] }}</code></div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center xl:justify-end">
                                <div class="min-w-[190px]">
                                    <label class="block text-xs font-semibold text-gray-600">Hiển thị</label>
                                    <select wire:model="layout.{{ $key }}" class="{{ $compactFieldClass }} mt-1">
                                        <option value="all">Desktop + Mobile</option>
                                        <option value="desktop">Chỉ Desktop</option>
                                        <option value="mobile">Chỉ Mobile</option>
                                        <option value="none">Ẩn hoàn toàn</option>
                                    </select>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:pt-5">
                                    @if($card['admin'])
                                        @if($card['admin']['type'] === 'route')
                                            <a href="{{ $card['admin']['url'] }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">{{ $card['admin']['label'] }} ↗</a>
                                        @else
                                            <button type="button" wire:click="setTab('{{ $card['admin']['tab'] }}')" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">⚙ {{ $card['admin']['label'] }}</button>
                                        @endif
                                    @endif

                                    @if($card['duplicatable'])
                                        <button type="button" wire:click="duplicateSection('{{ $key }}')" wire:loading.attr="disabled" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">⧉ Nhân bản</button>
                                    @endif

                                    @if(in_array($state, ['none', 'hidden'], true))
                                        <button type="button" wire:click="restoreSection('{{ $key }}')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">↻ Khôi phục</button>
                                    @else
                                        <button type="button" wire:click="removeSection('{{ $key }}')" wire:confirm="Bạn chắc chắn muốn {{ $card['is_copy'] ? 'xóa' : 'ẩn' }} section này?" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">{{ $card['is_copy'] ? '× Xóa' : '× Ẩn' }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    @if($activeTab === 'data')
        <div class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4"><h3 class="font-bold text-gray-900">Danh mục nổi bật</h3><p class="mt-1 text-sm text-gray-500">Chọn các danh mục được đưa vào section Categories.</p></div>
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
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><h3 class="font-bold text-gray-900">Sản phẩm nổi bật</h3><p class="mt-1 text-sm text-gray-500">Đã chọn {{ count($data['featured_ids']) }} sản phẩm.</p></div>
                    <button type="button" wire:click="openProductPicker" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">+ Chọn sản phẩm</button>
                </div>
                @forelse($selectedProducts as $product)
                    <div class="mb-2 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <img src="{{ $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image)) : 'https://placehold.co/80' }}" alt="{{ $product->title }}" class="h-12 w-12 rounded-lg object-cover">
                        <div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold text-gray-900">{{ $product->title }}</div><div class="text-xs text-gray-500">ID: {{ $product->id }}</div></div>
                        <button type="button" wire:click="toggleProduct({{ $product->id }})" class="rounded-lg border border-red-200 bg-white px-2.5 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa</button>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-sm text-gray-500">Chưa chọn sản phẩm nổi bật.</div>
                @endforelse
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4"><h3 class="font-bold text-gray-900">Auto Query</h3><p class="mt-1 text-sm text-gray-500">Giới hạn số item cho các section lấy dữ liệu tự động.</p></div>
                <div class="grid gap-5 md:grid-cols-3">
                    <div><label class="block text-sm font-semibold text-gray-700">Hàng mới về</label><input type="number" min="1" max="50" wire:model="newArrivalsCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Theo ngày tạo mới nhất.</p></div>
                    <div><label class="block text-sm font-semibold text-gray-700">Top bán chạy</label><input type="number" min="1" max="50" wire:model="bestSellersCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Theo sold_count.</p></div>
                    <div><label class="block text-sm font-semibold text-gray-700">Tin tức mới nhất</label><input type="number" min="1" max="10" wire:model="blogCount" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Số bài viết mới nhất.</p></div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-4"><div><h3 class="font-bold text-gray-900">Banner Quảng cáo</h3><p class="mt-1 text-sm text-gray-500">Nội dung CTA riêng, không phải Hero Banner Slider.</p></div><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" wire:model="promoBanner.show" class="{{ $checkClass }}"> Hiển thị</label></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-4">
                        <x-image-upload wire:model="newPromoImage" :newImage="$newPromoImage" :oldImage="$promoBanner['image']" label="Banner Quảng Cáo" />
                        <div><label class="block text-sm font-semibold text-gray-700">Image URL dự phòng</label><input type="text" wire:model="promoBanner.image" placeholder="https://..." class="{{ $fieldClass }}"></div>
                    </div>
                    <div class="space-y-4">
                        <div><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="promoBanner.title" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-sm font-semibold text-gray-700">Mô tả</label><textarea wire:model="promoBanner.sub_title" rows="3" class="{{ $fieldClass }}"></textarea></div>
                        <div class="grid gap-4 sm:grid-cols-2"><div><label class="block text-sm font-semibold text-gray-700">Nhãn nút</label><input type="text" wire:model="promoBanner.btn_text" class="{{ $fieldClass }}"></div><div><label class="block text-sm font-semibold text-gray-700">URL nút</label><input type="text" wire:model="promoBanner.link" class="{{ $fieldClass }}"></div></div>
                        <div><label class="block text-sm font-semibold text-gray-700">URL xem chi tiết</label><input type="text" wire:model="promoBanner.details_link" class="{{ $fieldClass }}"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-4"><div><h3 class="font-bold text-gray-900">Newsletter</h3><p class="mt-1 text-sm text-gray-500">Nội dung form đăng ký trên Homepage.</p></div><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" wire:model="newsletter.show" class="{{ $checkClass }}"> Hiển thị</label></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label class="block text-sm font-semibold text-gray-700">Badge</label><input type="text" wire:model="newsletter.badge" class="{{ $fieldClass }}"></div>
                    <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="newsletter.title" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Giữ hỗ trợ HTML hiện tại để tương thích dữ liệu cũ.</p></div>
                    <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Mô tả</label><textarea wire:model="newsletter.description" rows="3" class="{{ $fieldClass }}"></textarea></div>
                </div>
            </section>
        </div>
    @endif

    @if($activeTab === 'trust_badges')
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-bold text-gray-900">Cam kết / Icon service</h3><p class="mt-1 text-sm text-gray-500">Quản lý các item dịch vụ hiển thị trên Homepage.</p></div><button type="button" wire:click="addBadge" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">+ Thêm cam kết</button></div>
            <div class="space-y-3">
                @forelse($data['trust_badges'] as $index => $badge)
                    <div wire:key="homepage-trust-badge-{{ $index }}" class="grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-12 md:items-end">
                        <div class="md:col-span-4"><label class="block text-sm font-semibold text-gray-700">Icon / Image URL</label><input type="text" wire:model.live="data.trust_badges.{{ $index }}.icon" placeholder="fa-solid fa-truck hoặc https://..." class="{{ $fieldClass }}"></div>
                        <div class="md:col-span-3"><label class="block text-sm font-semibold text-gray-700">Tiêu đề</label><input type="text" wire:model="data.trust_badges.{{ $index }}.title" class="{{ $fieldClass }}"></div>
                        <div class="md:col-span-4"><label class="block text-sm font-semibold text-gray-700">Mô tả phụ</label><input type="text" wire:model="data.trust_badges.{{ $index }}.sub_title" class="{{ $fieldClass }}"></div>
                        <button type="button" wire:click="removeBadge({{ $index }})" class="rounded-lg border border-red-200 bg-white px-3 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 md:col-span-1">Xóa</button>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500">Chưa có cam kết. Nhấn <strong>+ Thêm cam kết</strong> để tạo.</div>
                @endforelse
            </div>
        </section>
    @endif

    <div class="sticky bottom-4 z-20 mt-6 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur">
        <span class="hidden text-xs text-gray-500 sm:inline">Các thay đổi Builder chỉ publish sau khi lưu.</span>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,newPromoImage" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="save">Lưu thay đổi</span><span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>

    @if($showProductPicker)
        <div class="fixed inset-0 z-[999] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/60" wire:click="$set('showProductPicker', false)"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
                    <div class="border-b border-gray-200 p-5"><h3 class="font-bold text-gray-900">Chọn sản phẩm nổi bật</h3><input type="text" wire:model.live.debounce.300ms="productSearchQuery" placeholder="Tìm theo tên sản phẩm..." class="{{ $fieldClass }}"></div>
                    <div class="max-h-[55vh] space-y-2 overflow-y-auto bg-gray-50 p-5">
                        @forelse($searchProducts as $product)
                            @php($selected = in_array($product->id, $data['featured_ids']))
                            <button type="button" wire:click="toggleProduct({{ $product->id }})" class="flex w-full items-center gap-3 rounded-lg border p-3 text-left transition {{ $selected ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <img src="{{ $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image)) : 'https://placehold.co/80' }}" alt="{{ $product->title }}" class="h-12 w-12 rounded-lg object-cover">
                                <div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold text-gray-900">{{ $product->title }}</div><div class="text-xs text-gray-500">{{ number_format($product->regular_price) }}đ</div></div>
                                <span class="text-xs font-semibold {{ $selected ? 'text-indigo-700' : 'text-gray-400' }}">{{ $selected ? 'Đã chọn' : 'Chọn' }}</span>
                            </button>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">{{ $productSearchQuery ? 'Không tìm thấy sản phẩm phù hợp.' : 'Nhập từ khóa để tìm sản phẩm.' }}</div>
                        @endforelse
                    </div>
                    <div class="flex justify-end border-t border-gray-200 bg-white p-4"><button type="button" wire:click="$set('showProductPicker', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đóng</button></div>
                </div>
            </div>
        </div>
    @endif
</div>
