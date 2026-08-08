<div class="mx-auto max-w-5xl">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $categoryId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Quản lý danh mục theo từng loại hệ thống.</p>
        </div>

        <a href="{{ route('admin.category.index') }}"
            class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm hover:bg-gray-50">
            Hủy
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <label class="text-sm font-medium text-gray-900">Tên danh mục *</label>
                    <input type="text" wire:model.live.debounce.300ms="name"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-900">Slug</label>
                    <input type="text" wire:model.live="slug"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('slug')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <label class="text-sm font-medium text-gray-900">Loại đối tượng</label>
                    <div class="mt-1 flex gap-2">
                        <select wire:model.live="type"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <option value="">-- Chọn loại --</option>
                            @foreach ($this->types as $categoryType)
                                <option value="{{ $categoryType->type }}">
                                    {{ $categoryType->icon }} {{ $categoryType->title }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" wire:click="openTypeModal"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-3 hover:bg-gray-50"
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
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
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
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center justify-between text-sm font-medium">
                    <span>Hiển thị</span>
                    <input type="checkbox" wire:model.live="is_active">
                </label>
                @error('is_active')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <x-category::image-upload
                label="Ảnh danh mục"
                wire:model="newImage"
                :old-image="$oldImage"
                :new-image="$newImage" />

            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Lưu danh mục</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
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
