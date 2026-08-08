<div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ $productId ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Điền đầy đủ thông tin để hiển thị sản phẩm trên website.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.products.index') }}"
                class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                Hủy bỏ
            </a>
            <button wire:click="save"
                class="flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition-all">
                <span wire:loading.remove>Lưu Sản Phẩm</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Đang xử lý...
                </span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-8">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
                <div class="p-6 space-y-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 border-b pb-2">Thông tin chung</h3>

                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Tên sản phẩm <span
                                class="text-red-500">*</span></label>
                        <div class="relative mt-2 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                            </div>
                            <input type="text" wire:model.live="title"
                                class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                placeholder="Nhập tên sản phẩm...">
                        </div>
                        @error('title')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Đường dẫn (Slug)</label>
                        <div class="relative mt-2 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                    </path>
                                </svg>
                            </div>
                            <input type="text" wire:model="slug"
                                class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-gray-50">
                        </div>
                        @error('slug')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Mô tả ngắn</label>
                        <div class="mt-2">
                            <textarea wire:model="short_description" rows="3"
                                class="block w-full rounded-md border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                        </div>
                    </div> --}}

                    <div class="mt-6">

                        <div wire:key="editor-instance-short-description">
                            <x-editor wire:model="short_description" label="Mô tả ngắn" mode="simple" height="100px" />
                        </div>
                    </div>

                    <div class="mt-6">

                        <div wire:key="editor-instance-description">
                            <x-editor
                                wire:model="description"
                                label="Chi tiết sản phẩm"
                                mode="full"
                                height="300px"
                                required="true"
                            />
                        </div>
                    </div>

                </div>
            </div>

            <x-gallery label="Thư viện ảnh sản phẩm" wire:model="newGallery" :gallery="$gallery" :newGallery="$newGallery"
                removeOldAction="removeOldGallery" removeNewAction="removeNewGallery" />
        </div>

        <div class="space-y-6">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4 border-b pb-2">Thiết lập bán hàng</h3>

                <div class="flex items-center justify-between mb-6">
                    <span class="flex-grow flex flex-col">
                        <span class="text-sm font-medium text-gray-900">Hiển thị</span>
                        <span class="text-xs text-gray-500">Bật để khách hàng thấy sản phẩm</span>
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                        </div>
                    </label>
                </div>

                <x-currency-input wire:model="regular_price" label="Giá bán thường" required="true" class="mb-4" />

                <x-currency-input wire:model="sale_price" label="Giá khuyến mãi" class="mb-4" />
                <hr class="my-6 border-gray-100">

        {{-- PHẦN MỚI: CẤU HÌNH HOA HỒNG AFFILIATE --}}
        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider">Cấu hình Affiliate</h4>
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">Tỷ lệ hoa hồng (%)</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <input type="number" 
                           step="0.01" 
                           wire:model="affiliate_commission_rate"
                           class="block w-full rounded-md border-0 py-2.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm"
                           placeholder="Mặc định: 10">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="text-gray-500 sm:text-sm">%</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <div class="flex-shrink-0">
                        <svg class="h-4 w-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-[11px] text-blue-700 leading-relaxed italic">
                        Lưu ý: Nếu để trống, hệ thống sẽ tự động áp dụng mức 10% hoa hồng chung.
                    </p>
                </div>
            </div>
        </div>
 

            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4 border-b pb-2">Phân loại</h3>

                <x-category-select label="Danh mục sản phẩm" :categories="$this->categories" wire:model="category_ids" />

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">Tags / Từ khóa</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model="tagInput" wire:keydown.enter.prevent="addTag"
                            placeholder="Nhập rồi Enter..."
                            class="block w-full rounded-md border-0 py-2 pl-9 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>

                    @if (count($tags) > 0)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($tags as $index => $tag)
                                <span
                                    class="inline-flex items-center gap-x-1 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                    {{ $tag }}
                                    <button type="button" wire:click="removeTag({{ $index }})"
                                        class="group relative -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-indigo-600/20">
                                        <svg viewBox="0 0 14 14"
                                            class="h-3.5 w-3.5 stroke-indigo-700/50 group-hover:stroke-indigo-700/75">
                                            <path d="M4 4l6 6m0-6l-6 6" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <x-image-upload label="Ảnh đại diện" wire:model="newImage" :oldImage="$oldImage" :newImage="$newImage" />

        </div>
    </div>
</div>
