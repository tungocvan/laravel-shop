<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ $categoryId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Phân loại sản phẩm/bài viết cho hệ thống.</p>
        </div>
        <a href="{{ route('admin.product-categories.index') }}" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-all">
            Hủy bỏ
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 space-y-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 border-b pb-2">Thông tin cơ bản</h3>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900">Tên danh mục <span class="text-red-500">*</span></label>
                    <div class="relative mt-2 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                             <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <input type="text" wire:model.live="name"
                            class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Ví dụ: Thời trang nam">
                    </div>
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900">Đường dẫn (Slug)</label>
                    <div class="relative mt-2 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">/</span>
                        </div>
                        <input type="text" wire:model="slug"
                            class="block w-full rounded-md border-0 py-2.5 pl-8 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-gray-50">
                    </div>
                    @error('slug') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-6">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 space-y-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 border-b pb-2">Cấu hình hiển thị</h3>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">Loại đối tượng</label>
                    <select wire:model.live="type" class="block w-full rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-gray-50 cursor-pointer">
                        <option value="product">🛍️ Sản phẩm (Product)</option>
                        <option value="post">📝 Bài viết (Post/News)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">Danh mục cha</label>
                    <select wire:model="parent_id" class="block w-full rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 cursor-pointer">
                        <option value="">-- Là danh mục gốc --</option>
                        @foreach($this->parents as $p)
                            <option value="{{ $p->id }}">{{ $p->view_name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Hiển thị danh mục cha thuộc nhóm: <strong class="uppercase text-indigo-600">{{ $type }}</strong>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">Thứ tự</label>
                    <input type="number" wire:model="sort_order" class="block w-full rounded-md border-0 py-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="flex-grow flex flex-col">
                        <span class="text-sm font-medium text-gray-900">Hiển thị</span>
                        <span class="text-xs text-gray-500">Bật/Tắt</span>
                    </span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <x-image-upload
                label="Ảnh bìa danh mục"
                wire:model="newImage"
                :oldImage="$oldImage"
                :newImage="$newImage"
            />

            <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition-all">
                <span wire:loading.remove>Lưu Danh Mục</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Đang lưu...
                </span>
            </button>
        </div>
    </form>
</div>
