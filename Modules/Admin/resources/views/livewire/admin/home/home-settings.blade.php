<div class="max-w-6xl mx-auto pb-20">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quản trị Trang Chủ</h1>
            <p class="text-sm text-gray-500 mt-1">Tùy chỉnh bố cục, nội dung hiển thị và các khối chức năng.</p>
        </div>
        <button wire:click="save" wire:loading.attr="disabled"
            class="btn-primary flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow-sm font-medium transition-all">
            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span wire:loading.remove wire:target="save">Lưu thay đổi</span>
            <span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            @foreach ([
        'layout' => 'Bố cục & Hiển thị',
        'data' => 'Dữ liệu Danh mục & Sản phẩm',
        'trust_badges' => 'Cam kết (Trust Badges)',
    ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')"
                    class="{{ $activeTab === $key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">

        {{-- TAB 1: LAYOUT CONTROL --}}
        @if ($activeTab === 'layout')
            <div class="grid grid-cols-1 gap-4 animate-fadeIn">
                @php
                    $sections = [
                        'show_hero' => ['Banner Slider', 'Slider chính đầu trang'],
                        'show_categories' => ['Danh mục nổi bật', 'Lưới hình ảnh danh mục'],
                        'show_flash_sale' => ['Flash Sale', 'Sản phẩm giảm giá có đếm ngược'],
                        'show_featured' => ['Sản phẩm nổi bật', 'Khối sản phẩm ghim thủ công'],
                        'show_new_arrivals' => ['Hàng mới về', 'Sản phẩm mới nhất tự động'],
                        'show_best_sellers' => ['Top bán chạy', 'Sản phẩm bán chạy nhất'],
                        'show_blog_highlight' => ['Tin tức / Blog', 'Bài viết mới nhất'],
                        'show_promo_banner' => ['Banner Quảng cáo', 'Quảng cáo Banner'],
                        'show_trust_badges' => ['Các Icon service', 'Các dịch vụ quảng cáo'],
                        'show_newsletter' => ['Newletter', 'Liên hệ'],
                    ];
                @endphp

                @foreach ($sections as $key => $info)
                    <div
                        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg bg-white hover:border-indigo-300 transition shadow-sm">

                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                {{-- Icon tùy biến hoặc mặc định --}}
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">{{ $info[0] }}</h3>
                                <p class="text-xs text-gray-500">{{ $info[1] }}</p>
                            </div>
                        </div>

                        <div class="w-48">
                            <select wire:model="layout.{{ $key }}"
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 cursor-pointer">
                                <option value="all">Hiện tất cả</option>
                                <option value="desktop">Chỉ hiện Desktop</option>
                                <option value="mobile">Chỉ hiện Mobile</option>
                                <option value="none">Ẩn hoàn toàn</option>
                            </select>

                            {{-- Hiển thị label nhỏ trạng thái --}}
                            <div class="mt-1 text-right">
                                @if ($layout[$key] == 'all')
                                    <span class="text-[10px] text-green-600 font-medium">● Hiển thị Full</span>
                                @elseif($layout[$key] == 'desktop')
                                    <span class="text-[10px] text-blue-600 font-medium">● Chỉ PC</span>
                                @elseif($layout[$key] == 'mobile')
                                    <span class="text-[10px] text-orange-600 font-medium">● Chỉ Mobile</span>
                                @else
                                    <span class="text-[10px] text-gray-400 font-medium">● Đang ẩn</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- TAB 2: DATA CONTROL --}}
        @if ($activeTab === 'data')
            <div class="space-y-8 animate-fadeIn">

                <div>
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Danh mục nổi bật</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach ($allCategories as $cat)
                            <label
                                class="relative flex items-center p-3 rounded-lg border cursor-pointer hover:bg-gray-50 {{ in_array($cat->id, $data['category_ids']) ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200' }}">
                                <input type="checkbox" value="{{ $cat->id }}" wire:model="data.category_ids"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 mr-3">
                                <span class="text-sm font-medium text-gray-900">{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Chọn các danh mục bạn muốn hiển thị ở Section "Danh mục nổi
                        bật".</p>
                </div>

                <div class="border-t border-gray-100"></div>

                {{-- KHU VỰC CHỌN SẢN PHẨM NỔI BẬT --}}
                <div class="mt-6 border-t pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-bold text-gray-700">
                            Sản phẩm nổi bật (Đã chọn: <span
                                class="text-indigo-600">{{ count($data['featured_ids']) }}</span>)
                        </label>

                        <button wire:click="openProductPicker" type="button"
                            class="text-sm bg-indigo-50 text-indigo-700 px-3 py-1 rounded hover:bg-indigo-100 font-medium transition">
                            + Chọn sản phẩm
                        </button>
                    </div>

                    {{-- LIST SẢN PHẨM ĐÃ CHỌN (PREVIEW) --}}
                    @if (count($selectedProducts) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($selectedProducts as $p)
                                <div
                                    class="flex items-center gap-3 p-2 bg-white border border-gray-200 rounded shadow-sm relative group">

                                    {{-- 1. FIX ẢNH HIỂN THỊ (Preview List) --}}
                                    <div class="w-12 h-12 flex-shrink-0 bg-gray-100 rounded overflow-hidden">
                                        <img src="{{ $p->image ? (str_starts_with($p->image, 'http') ? $p->image : asset('storage/' . $p->image)) : 'https://placehold.co/100' }}"
                                            class="w-full h-full object-cover" alt="Img">
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate"
                                            title="{{ $p->title }}">{{ $p->title }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $p->id }}</p>
                                    </div>

                                    {{-- Nút Xóa nhanh --}}
                                    <button wire:click="toggleProduct({{ $p->id }})"
                                        class="text-gray-400 hover:text-red-500 p-1 rounded-full transition">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div
                            class="text-sm text-gray-500 italic bg-gray-50 p-4 rounded text-center border border-dashed border-gray-300">
                            Chưa có sản phẩm nổi bật nào. Bấm "Chọn sản phẩm" để thêm.
                        </div>
                    @endif
                </div>

                {{-- MODAL CHỌN SẢN PHẨM (PRODUCT PICKER) --}}
                @if ($showProductPicker)
                    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                        aria-modal="true">
                        <div
                            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                            {{-- Overlay --}}
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                                wire:click="$set('showProductPicker', false)"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                aria-hidden="true">&#8203;</span>

                            <div
                                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">

                                {{-- Header Modal --}}
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Chọn sản
                                        phẩm nổi bật</h3>

                                    {{-- Search Box --}}
                                    <div class="mt-4 relative">
                                        <input type="text" wire:model.live.debounce.300ms="productSearchQuery"
                                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md pl-10"
                                            placeholder="Tìm kiếm theo tên sản phẩm...">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- Body List --}}
                                <div class="bg-gray-50 px-4 py-4 sm:p-6 h-96 overflow-y-auto">
                                    <div class="grid grid-cols-1 gap-3">
                                        @forelse($searchProducts as $product)
                                            @php
                                                $isSelected = in_array($product->id, $data['featured_ids']);

                                                // --- LOGIC SỬA LỖI ẢNH (QUAN TRỌNG) ---
                                                $imageUrl = 'https://placehold.co/100'; // Ảnh mặc định nếu rỗng

                                                if (!empty($product->image)) {
                                                    // TRƯỜNG HỢP 1: Ảnh Online (Unsplash, Lorempixel...) -> GIỮ NGUYÊN
                                                    if (str_starts_with($product->image, 'http')) {
                                                        $imageUrl = $product->image;
                                                    }
                                                    // TRƯỜNG HỢP 2: Ảnh Upload (Local) -> Thêm storage/
                                                    else {
                                                        // Xử lý kỹ: nếu trong DB lỡ lưu chữ 'storage/' rồi thì không thêm nữa
                                                        $cleanPath = trim($product->image, '/'); // Xóa dấu / ở đầu nếu có
                                                        if (str_starts_with($cleanPath, 'storage/')) {
                                                            $imageUrl = asset($cleanPath);
                                                        } else {
                                                            $imageUrl = asset('storage/' . $cleanPath);
                                                        }
                                                    }
                                                }
                                            @endphp

                                            <div wire:click="toggleProduct({{ $product->id }})"
                                                class="flex items-center p-3 rounded-lg border cursor-pointer transition select-none gap-3
                                                        {{ $isSelected ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 bg-white hover:border-indigo-300' }}">

                                                {{-- IMAGE CONTAINER --}}
                                                <div
                                                    class="h-12 w-12 flex-shrink-0 rounded bg-gray-200 overflow-hidden relative border border-gray-100">
                                                    <img src="{{ $imageUrl }}" class="h-full w-full object-cover"
                                                        onerror="this.src='https://placehold.co/100?text=Err'">

                                                    {{-- Icon Check Active --}}
                                                    @if ($isSelected)
                                                        <div
                                                            class="absolute inset-0 bg-indigo-600/20 flex items-center justify-center">
                                                            <div class="bg-indigo-600 rounded-full p-0.5">
                                                                <svg class="w-3 h-3 text-white" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="3"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <h4
                                                        class="text-sm font-medium text-gray-900 truncate {{ $isSelected ? 'text-indigo-700' : '' }}">
                                                        {{ $product->title }}
                                                    </h4>
                                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                                        <span>ID: {{ $product->id }}</span>
                                                        <span>•</span>
                                                        <span>{{ number_format($product->regular_price) }}đ</span>
                                                    </div>
                                                </div>

                                                {{-- Trạng thái text --}}
                                                <div class="ml-4 flex-shrink-0">
                                                    @if ($isSelected)
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                            Đã chọn
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400 group-hover:text-gray-500">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-10 text-gray-500">
                                                @if ($productSearchQuery)
                                                    Không tìm thấy sản phẩm nào khớp với "{{ $productSearchQuery }}"
                                                @else
                                                    Nhập tên sản phẩm để tìm kiếm...
                                                @endif
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Footer Modal --}}
                                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                                    <button type="button" wire:click="$set('showProductPicker', false)"
                                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                        Đóng
                                    </button>
                                </div>
                            </div>


                        </div>
                    </div>
                @endif

                {{-- ... Code phần Chọn Danh Mục & Sản Phẩm Nổi Bật ở trên giữ nguyên ... --}}


                <div class="border-t border-gray-100"></div>
                <div class="mt-8 border-t pt-6">
                    <h3 class="font-bold text-gray-800 mb-4">Cấu hình Tự Động (Auto Query)</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Khối New Arrivals (Cũ) --}}
                        <div class="bg-blue-50 p-4 rounded border border-blue-100">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Hàng Mới Về (Số lượng)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" wire:model="newArrivalsCount" min="4" max="20"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-24 sm:text-sm border-gray-300 rounded-md text-center">
                                <span class="text-gray-500 text-sm">sản phẩm</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-clock"></i> Lấy theo ngày tạo
                                mới nhất.</p>
                        </div>

                        {{-- Khối Best Sellers (MỚI) --}}
                        <div class="bg-orange-50 p-4 rounded border border-orange-100">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Top Bán Chạy (Số lượng)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" wire:model="bestSellersCount" min="4" max="20"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-24 sm:text-sm border-gray-300 rounded-md text-center">
                                <span class="text-gray-500 text-sm">sản phẩm</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-fire"></i> Lấy theo
                                <code>sold_count</code> cao nhất.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ... Khối New Arrivals & Best Sellers ở trên ... --}}
                <div class="border-t border-gray-100"></div>
                <div class="mt-8 border-t pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-800">Cấu hình Banner Quảng Cáo (Promo Banner)</h3>

                        {{-- Toggle Bật/Tắt --}}
                        <div class="flex items-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="promoBanner.show" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600">
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">Hiển thị</span>
                            </label>
                        </div>
                    </div>

                    {{-- Form nhập liệu --}}
                    <div class="bg-gray-50 p-6 rounded border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Cột 1: Hình ảnh --}}
                        <div>
                            <x-image-upload wire:model="newPromoImage" :newImage="$newPromoImage" :oldImage="$promoBanner['image']"
                                label="Banner Quảng Cáo" />

                            {{-- Ô nhập link thủ công (Giữ lại để phòng hờ) --}}
                            <div x-data="{ open: false }" class="mt-3">
                                <button type="button" @click="open = !open"
                                    class="text-xs text-gray-500 underline hover:text-indigo-600">
                                    Nhập link ảnh thủ công (URL)
                                </button>
                                <div x-show="open" class="mt-2 transition-all">
                                    <input type="text" wire:model="promoBanner.image"
                                        class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm text-gray-600"
                                        placeholder="https://...">
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 mt-2 italic">Khuyên dùng: Ảnh ngang, tỷ lệ 3:1 (VD:
                                1200x400px)</p>
                        </div>

                        {{-- Cột 2: Nội dung --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tiêu đề chính
                                    (Heading)</label>
                                <input type="text" wire:model="promoBanner.title"
                                    class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Mô tả phụ (Subtitle)</label>
                                <textarea wire:model="promoBanner.sub_title" rows="2"
                                    class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Chữ trên nút</label>
                                    <input type="text" wire:model="promoBanner.btn_text"
                                        class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Link nút bấm</label>
                                    <input type="text" wire:model="promoBanner.link"
                                        class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Link "Xem chi tiết" (Tùy
                                    chọn)</label>
                                <input type="text" wire:model="promoBanner.details_link"
                                    class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"
                                    placeholder="Để trống nếu không muốn hiện">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                {{-- KHỐI CẤU HÌNH BLOG HIGHLIGHT --}}
                <div class="bg-purple-50 p-4 rounded border border-purple-100 mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Tin tức mới nhất (Số
                                lượng)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" wire:model="blogCount" min="1" max="10"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-24 sm:text-sm border-gray-300 rounded-md text-center">
                                <span class="text-gray-500 text-sm">bài viết</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fa-solid fa-newspaper"></i> Lấy bài viết mới nhất từ bảng <code>posts</code>.
                            </p>
                        </div>

                        {{-- Toggle Bật/Tắt nhanh layout Blog --}}
                        <div class="flex items-center">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <span class="text-sm font-medium text-gray-700">Hiển thị ở trang chủ?</span>
                                <div class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="layout.show_blog_highlight"
                                        class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600">
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                {{-- KHỐI CẤU HÌNH NEWSLETTER (FOOTER) --}}
                <div class="bg-gray-900 text-white p-6 rounded-xl border border-gray-700 mt-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                            Cấu hình Newsletter (Form đăng ký)
                        </h3>

                        {{-- Toggle Bật/Tắt --}}
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="newsletter.show" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-300">Hiển thị</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Badge nhỏ (Góc trên)</label>
                            <input type="text" wire:model="newsletter.badge"
                                class="block w-full rounded-md border-gray-600 bg-gray-800 text-white shadow-sm sm:text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Tiêu đề lớn (Hỗ trợ HTML)</label>
                            <input type="text" wire:model="newsletter.title"
                                class="block w-full rounded-md border-gray-600 bg-gray-800 text-white shadow-sm sm:text-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="text-[10px] text-gray-500 mt-1">Ví dụ: Mở khóa ưu đãi &lt;span
                                class="text-blue-400"&gt;10%&lt;/span&gt;...</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1">Mô tả chi tiết</label>
                            <textarea wire:model="newsletter.description" rows="2"
                                class="block w-full rounded-md border-gray-600 bg-gray-800 text-white shadow-sm sm:text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        @endif


        {{-- TAB 3: TRUST BADGES (REPEATER) --}}
        @if ($activeTab === 'trust_badges')
            <div class="animate-fadeIn space-y-6">

                {{-- Header hướng dẫn --}}
                <div class="bg-blue-50 text-blue-700 px-4 py-3 rounded-lg text-sm flex items-start gap-2">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong>Lưu ý:</strong> Bạn có thể nhập class icon của FontAwesome (ví dụ: <code>fa-solid
                            fa-truck</code>) hoặc dán đường dẫn ảnh (URL) vào ô Icon.
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Kiểm tra nếu mảng tồn tại thì mới lặp --}}
                    @if (isset($data['trust_badges']) && count($data['trust_badges']) > 0)
                        @foreach ($data['trust_badges'] as $index => $badge)
                            <div class="flex gap-4 items-start p-4 bg-gray-50 border border-gray-200 rounded-lg group hover:border-indigo-300 transition shadow-sm relative"
                                wire:key="badge-{{ $index }}"> {{-- wire:key rất quan trọng khi dùng repeater --}}

                                {{-- Số thứ tự --}}
                                <div class="pt-2">
                                    <span
                                        class="flex items-center justify-center w-8 h-8 rounded-full bg-white border border-gray-200 text-xs font-bold text-gray-600 shadow-sm">
                                        {{ $index + 1 }}
                                    </span>
                                </div>

                                {{-- Form Inputs --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                                    {{-- 1. Icon --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">
                                            Icon / Ảnh
                                        </label>
                                        <div class="relative">
                                            <input type="text"
                                                wire:model.live="data.trust_badges.{{ $index }}.icon"
                                                placeholder="fa-solid fa-truck hoặc Link ảnh"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-9">
                                            {{-- Preview Icon nhỏ trong input --}}
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fa-solid fa-icons"></i>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. Tiêu đề --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Tiêu đề chính</label>
                                        <input type="text"
                                            wire:model="data.trust_badges.{{ $index }}.title"
                                            placeholder="VD: Miễn phí vận chuyển"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium">
                                    </div>

                                    {{-- 3. Mô tả phụ (Dùng sub_title để khớp với frontend) --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Mô tả phụ</label>
                                        <input type="text"
                                            wire:model="data.trust_badges.{{ $index }}.sub_title"
                                            placeholder="VD: Đơn hàng > 500k"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-500">
                                    </div>
                                </div>

                                {{-- Button Xóa --}}
                                <button wire:click="removeBadge({{ $index }})"
                                    class="absolute top-2 right-2 md:static md:mt-7 text-gray-400 hover:text-red-500 p-1.5 rounded-full hover:bg-red-50 transition"
                                    title="Xóa mục này">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                            <p class="text-gray-500 mb-2">Chưa có cam kết nào.</p>
                        </div>
                    @endif
                </div>

                {{-- Button Thêm Mới --}}
                <div class="mt-4">
                    <button wire:click="addBadge" type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        <svg class="-ml-1 mr-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Thêm Cam Kết
                    </button>
                </div>
            </div>
        @endif

    </div>
    @if ($showProductPicker)
        <div class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                wire:click="$set('showProductPicker', false)"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4" id="modal-title">Chọn sản phẩm
                        </h3>

                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="productSearchQuery"
                                placeholder="Gõ tên sản phẩm để tìm..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 border">
                        </div>

                        <div class="max-h-60 overflow-y-auto space-y-2 border-t border-gray-100 pt-2">
                            @forelse($searchProducts as $prod)
                                <div wire:click="toggleProduct({{ $prod->id }})"
                                    class="flex items-center gap-3 p-2 rounded cursor-pointer hover:bg-gray-50 transition {{ in_array($prod->id, $data['featured_ids']) ? 'bg-indigo-50 ring-1 ring-indigo-200' : '' }}">

                                    <div class="shrink-0">
                                        <img src="{{ $prod->image ? (\Illuminate\Support\Str::startsWith($prod->image, ['http', 'https']) ? $prod->image : asset('storage/' . $prod->image)) : 'https://placehold.co/40' }}"
                                            class="h-10 w-10 object-cover rounded bg-gray-200"
                                            alt="{{ $prod->title ?? 'Product Image' }}"
                                            onerror="this.src='https://placehold.co/40?text=Err'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $prod->title }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($prod->regular_price) }}đ
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        @if (in_array($prod->id, $data['featured_ids']))
                                            <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-sm text-gray-500 py-4">
                                    {{ empty($productSearchQuery) ? 'Gõ từ khóa để tìm kiếm...' : 'Không tìm thấy sản phẩm nào.' }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="$set('showProductPicker', false)"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Xong</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Script để hiển thị Toast Notification --}}
@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-toast', (event) => {
                // Sử dụng thư viện Toast có sẵn (ví dụ SweetAlert2 hoặc Toastr)
                // Nếu chưa có, alert tạm hoặc tự build custom toast
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: event.type,
                        title: event.message
                    });
                } else {
                    alert(event.message); // Fallback
                }
            });
        });
    </script>
@endpush
