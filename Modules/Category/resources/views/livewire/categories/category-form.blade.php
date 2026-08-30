<div class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.category.index') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-indigo-600">
                <span aria-hidden="true">←</span>
                Quản lý danh mục
            </a>

            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">
                {{ $categoryId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
            </h2>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Thiết lập thông tin, phân loại, trạng thái hiển thị và hình ảnh cho danh mục.
            </p>
        </div>

        <a href="{{ route('admin.category.index') }}"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <span aria-hidden="true">←</span>
            Quay về danh sách
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <div class="space-y-6 xl:col-span-8">
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Thông tin danh mục</h3>
                    <p class="mt-1 text-sm text-gray-500">Tên và slug được sử dụng để nhận diện danh mục trong hệ thống.</p>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <label class="text-sm font-medium text-gray-900">Tên danh mục *</label>
                        <input type="text" wire:model.live.debounce.300ms="name"
                            class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-medium text-gray-900">Slug</label>
                            <span class="text-xs text-gray-400">Tự động tạo khi thêm mới</span>
                        </div>
                        <input type="text" wire:model.live="slug"
                            class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('slug')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 px-5 py-4 text-sm text-gray-600">
                <span class="font-semibold text-gray-800">Lưu ý:</span>
                Danh mục con chỉ có thể chọn danh mục cha thuộc cùng loại đối tượng.
            </div>
        </div>

        <aside class="space-y-6 xl:col-span-4">
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Thiết lập</h3>
                    <p class="mt-1 text-sm text-gray-500">Phân loại, vị trí và trạng thái hiển thị.</p>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label class="text-sm font-medium text-gray-900">Loại đối tượng</label>
                        <div class="mt-2 flex gap-2">
                            <select wire:model.live="type"
                                class="min-w-0 flex-1 rounded-xl border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="">-- Chọn loại --</option>
                                @foreach ($this->types as $categoryType)
                                    <option value="{{ $categoryType->type }}">
                                        {{ $categoryType->icon }} {{ $categoryType->title }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" wire:click="openTypeModal"
                                class="inline-flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white text-lg font-semibold text-gray-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600"
                                aria-label="Quản lý loại danh mục">
                                +
                            </button>
                        </div>
                        @error('type')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-900">Danh mục cha</label>
                        <select wire:model.live="parent_id"
                            class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <option value="">-- Root --</option>
                            @foreach ($this->parents as $parent)
                                <option value="{{ $parent['id'] }}">{{ $parent['label'] }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-900">Thứ tự</label>
                        <input type="number" min="0" wire:model.live="sort_order"
                            class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Hiển thị</p>
                            <p class="mt-0.5 text-xs text-gray-500">Cho phép danh mục xuất hiện ở các khu vực sử dụng.</p>
                        </div>
                        <input type="checkbox" wire:model.live="is_active"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </div>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <x-category::image-upload
                label="Ảnh danh mục"
                wire:model="newImage"
                :old-image="$oldImage"
                :new-image="$newImage" />

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <a href="{{ route('admin.category.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        <span aria-hidden="true">←</span>
                        Quay về danh sách
                    </a>

                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">Lưu danh mục</span>
                        <span wire:loading wire:target="save">Đang lưu...</span>
                    </button>
                </div>
            </div>
        </aside>
    </form>

    @if ($showTypeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/40 p-4">
            <div class="max-h-[90vh] w-full max-w-lg space-y-6 overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Quản lý loại đối tượng</h3>
                    <button type="button" wire:click="$set('showTypeModal', false)"
                        class="text-gray-400 hover:text-gray-600" aria-label="Đóng">
                        X
                    </button>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-900">Chọn loại để chỉnh sửa</label>
                    <select wire:model.live="selectedType"
                        class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tạo mới</option>
                        @foreach ($this->types as $categoryType)
                            <option value="{{ $categoryType->type }}">
                                {{ $categoryType->icon }} {{ $categoryType->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('selectedType')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @if (! $selectedType)
                    <div class="space-y-4 border-t pt-4">
                        <div>
                            <label class="text-sm font-medium">Type</label>
                            <input wire:model.live="newType"
                                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                            @error('newType')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium">Title</label>
                            <input wire:model.live="newTypeTitle"
                                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                            @error('newTypeTitle')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium">Icon</label>
                            <input wire:model.live="newTypeIcon"
                                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3"
                                placeholder="Icon hoặc emoji">
                            @error('newTypeIcon')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (auth('admin')->user()?->can('create_category'))
                            <button type="button" wire:click="createType" wire:loading.attr="disabled"
                                wire:target="createType"
                                class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                Tạo mới
                            </button>
                        @endif
                    </div>
                @else
                    <div class="space-y-4 border-t pt-4">
                        <div>
                            <label class="text-sm font-medium">Title</label>
                            <input wire:model.live="editTitle"
                                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                            @error('editTitle')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium">Icon</label>
                            <input wire:model.live="editIcon"
                                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3"
                                placeholder="Icon hoặc emoji">
                            @error('editIcon')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="editActive">
                            Active
                        </label>
                        @error('editActive')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="flex gap-3">
                            @if (auth('admin')->user()?->can('edit_category'))
                                <button type="button" wire:click="updateType" wire:loading.attr="disabled"
                                    wire:target="updateType"
                                    class="flex-1 rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                    Cập nhật
                                </button>
                            @endif

                            @if (auth('admin')->user()?->can('delete_category'))
                                <button type="button" wire:click="requestTypeDelete"
                                    class="rounded-xl border border-red-300 px-4 py-3 font-semibold text-red-600">
                                    Xóa
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($confirmingTypeDelete)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">Xác nhận xóa loại danh mục</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Chỉ loại chưa có danh mục phụ thuộc mới được xóa.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelTypeDelete"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium">
                        Hủy
                    </button>
                    <button type="button" wire:click="confirmTypeDelete" wire:loading.attr="disabled"
                        wire:target="confirmTypeDelete"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
