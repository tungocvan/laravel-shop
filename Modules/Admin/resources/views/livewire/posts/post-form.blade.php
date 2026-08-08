<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6">

    <form wire:submit="save">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight leading-tight">
                    {{ $isEdit ? 'Chỉnh sửa bài viết' : 'Thêm bài viết mới' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Điền đầy đủ thông tin để tối ưu hóa hiển thị và SEO.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.posts.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 shadow-sm text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Hủy bỏ
                </a>

                <button type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent shadow-sm text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                    <span wire:loading.remove>
                        {{ $isEdit ? 'Lưu thay đổi' : 'Đăng bài viết' }}
                    </span>
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Đang xử lý...
                    </span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

            <div class="lg:col-span-8 space-y-6">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 space-y-6">

                        <div>
                            <label for="name" class="block text-sm font-semibold leading-6 text-gray-900">
                                Tiêu đề bài viết <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2">
                                <input type="text" wire:model.live="name" id="name"
                                    class="block w-full rounded-lg border-0 py-3 px-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-lg font-medium sm:text-base sm:leading-6 transition-all"
                                    placeholder="Nhập tiêu đề hấp dẫn...">
                            </div>
                            @error('name') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-semibold leading-6 text-gray-900">
                                Đường dẫn (URL)
                            </label>
                            <div class="mt-2 flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 overflow-hidden">
                                <span class="flex select-none items-center pl-3 pr-1 text-gray-500 bg-gray-50 border-r border-gray-200 sm:text-sm">
                                    {{ url('/') }}/blog/
                                </span>
                                <input type="text" wire:model="slug" id="slug"
                                    class="block flex-1 border-0 bg-transparent py-2 pl-2 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="duong-dan-bai-viet">
                            </div>
                            @error('slug') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div wire:key="editor-wrapper-summary">
                            <x-editor
                                wire:model="summary"
                                label="Mô tả ngắn (Summary)"
                                placeholder="Tóm tắt nội dung chính..."
                                mode="simple"
                                height="120px"
                            />
                        </div>

                        <div wire:key="editor-wrapper-content">
                            <x-editor
                                wire:model="content"
                                label="Nội dung chi tiết"
                                mode="full"
                                height="450px"
                                required="true"
                            />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-base font-bold text-gray-900 flex items-center">
                                <svg class="h-5 w-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Cấu hình SEO
                            </h3>

                            <button type="button"
                                @click="$wire.set('meta_title', $wire.get('name')); $wire.set('meta_description', $wire.get('summary'));"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer transition-colors bg-indigo-50 px-2 py-1 rounded">
                                ✨ Tự động điền từ nội dung
                            </button>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <div class="flex justify-between">
                                    <label class="block text-sm font-semibold leading-6 text-gray-900">Meta Title</label>
                                    <span class="text-xs leading-6 {{ Str::length($meta_title) > 60 ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                                        {{ Str::length($meta_title) }}/60
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <input type="text" wire:model="meta_title"
                                        class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="Tiêu đề hiển thị trên Google">
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between">
                                    <label class="block text-sm font-semibold leading-6 text-gray-900">Meta Description</label>
                                    <span class="text-xs leading-6 {{ Str::length($meta_description) > 160 ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                                        {{ Str::length($meta_description) }}/160
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <textarea wire:model="meta_description" rows="3"
                                        class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 resize-none"
                                        placeholder="Mô tả ngắn gọn hiển thị trên kết quả tìm kiếm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-4 space-y-6">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Trạng thái đăng</h3>

                    <div class="space-y-4">
                        <div>
                            <select wire:model="status"
                                class="block w-full rounded-lg border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 cursor-pointer hover:bg-gray-50 transition-colors">
                                <option value="published">🟢 Công khai (Published)</option>
                                <option value="draft">⚪ Bản nháp (Draft)</option>
                                <option value="hidden">🔴 Ẩn bài viết (Hidden)</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <span class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900">Nổi bật</span>
                                <span class="text-xs text-gray-500">Ghim bài viết lên đầu</span>
                            </span>
                            <button type="button" wire:click="$toggle('is_featured')"
                                class="{{ $is_featured ? 'bg-indigo-600' : 'bg-gray-200' }} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                <span aria-hidden="true" class="{{ $is_featured ? 'translate-x-5' : 'translate-x-0' }} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-gray-900">Chuyên mục</h3>
                        <a href="{{ route('admin.product-categories.index') }}" target="_blank" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                            + Quản lý
                        </a>
                    </div>

                    <div class="max-h-60 overflow-y-auto pr-1 space-y-1 custom-scrollbar">
                        @forelse($categories as $cat)
                            <div class="relative flex items-start py-1.5">
                                <div class="min-w-0 flex-1 text-sm leading-6">
                                    <label for="cat-{{ $cat->id }}" class="select-none font-medium text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors">{{ $cat->name }}</label>
                                </div>
                                <div class="ml-3 flex h-6 items-center">
                                    <input id="cat-{{ $cat->id }}" wire:model="selectedCategories" value="{{ $cat->id }}" type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500 italic">Chưa có chuyên mục.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-2">Thẻ (Tags)</h3>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <input type="text" wire:model="inputTags"
                            class="block w-full rounded-lg border-0 py-2 pl-9 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Nhập thẻ, cách nhau dấu phẩy">
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Ảnh đại diện</h3>

                    <div class="w-full">
                        <label class="group relative block w-full aspect-[16/9] cursor-pointer rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 text-center hover:bg-gray-100 transition-all overflow-hidden focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">

                            @if ($new_thumbnail)
                                <img src="{{ $new_thumbnail->temporaryUrl() }}" class="absolute inset-0 h-full w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                    <p class="text-sm font-medium text-white">Bấm để thay đổi</p>
                                </div>

                            @elseif($thumbnail)
                                @php
                                    // Logic kiểm tra URL thông minh
                                    $isUrl = Str::startsWith($thumbnail, ['http://', 'https://']);
                                    $imageUrl = $isUrl ? $thumbnail : asset('storage/' . $thumbnail);
                                @endphp

                                <img src="{{ $imageUrl }}" class="absolute inset-0 h-full w-full object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=No+Image';">

                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                    <p class="text-sm font-medium text-white">Bấm để thay đổi</p>
                                </div>

                            @else
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-4">
                                    <svg class="h-10 w-10 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <span class="font-semibold text-indigo-600 hover:text-indigo-500">Tải ảnh lên</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Max 2MB</p>
                                </div>
                            @endif

                            <input type="file" wire:model="new_thumbnail" class="sr-only">

                            <div wire:loading wire:target="new_thumbnail" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
                                <svg class="animate-spin h-8 w-8 text-indigo-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span class="text-xs font-semibold text-indigo-600">Đang tải...</span>
                            </div>
                        </label>

                        @if(!$new_thumbnail && $thumbnail)
                            <button type="button" wire:click="$set('thumbnail', null)" class="mt-2 text-xs text-red-600 hover:text-red-800 hover:underline flex items-center justify-center w-full">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Xóa ảnh hiện tại
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
