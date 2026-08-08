<div>
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Danh mục</h2>
            <p class="mt-1 text-sm text-gray-500">Phân loại dữ liệu cho hệ thống website.</p>
        </div>

        @if (auth('admin')->user()?->can('create_category'))
            <a href="{{ route('admin.category.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                Thêm danh mục
            </a>
        @endif
    </div>

    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Loại danh mục">
            @foreach ($this->types as $categoryType)
                <button type="button"
                    wire:key="category-type-{{ $categoryType->type }}"
                    wire:click="setType('{{ $categoryType->type }}')"
                    class="flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition
                        {{ $type === $categoryType->type
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    <span>{{ $categoryType->icon }}</span>
                    {{ $categoryType->title }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <input type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Tìm theo tên hoặc slug"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">

        <select wire:model.live="status"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hiển thị</option>
            <option value="inactive">Đang ẩn</option>
        </select>

        <select wire:model.live="perPage"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }} dòng/trang</option>
            @endforeach
        </select>
    </div>

    @error('delete')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
        <div wire:loading.flex
            wire:target="setType,search,status,perPage,confirmDelete,setActive"
            class="absolute inset-0 z-10 items-center justify-center bg-white/70">
            <span class="text-sm font-medium text-indigo-600">Đang xử lý...</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Tên danh mục</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Danh mục cha</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500">Thứ tự</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-500">Trạng thái</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    @if ($category->image)
                                        <img class="h-10 w-10 rounded-lg border object-cover"
                                            src="{{ asset('storage/'.$category->image) }}"
                                            alt="{{ $category->name }}">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border bg-gray-100 text-sm font-semibold text-gray-500">
                                            {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $category->name }}</div>
                                        <div class="text-xs text-gray-500">/{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $category->parent?->name ?? 'Root' }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                {{ $category->sort_order }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if (auth('admin')->user()?->can('edit_category'))
                                    <button type="button"
                                        wire:click="setActive({{ $category->id }}, {{ $category->is_active ? 'false' : 'true' }})"
                                        class="rounded-full px-3 py-1 text-xs font-semibold
                                            {{ $category->is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-600' }}">
                                        {{ $category->is_active ? 'Hiện' : 'Ẩn' }}
                                    </button>
                                @else
                                    <span class="text-xs font-semibold {{ $category->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $category->is_active ? 'Hiện' : 'Ẩn' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                @if (auth('admin')->user()?->can('edit_category'))
                                    <a href="{{ route('admin.category.edit', $category->id) }}"
                                        class="mr-3 text-indigo-600 hover:text-indigo-900">Sửa</a>
                                @endif

                                @if (auth('admin')->user()?->can('delete_category'))
                                    <button type="button"
                                        wire:click="requestDelete({{ $category->id }})"
                                        class="text-red-600 hover:text-red-900">
                                        Xóa
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-gray-500">
                                Không có danh mục phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    @if ($pendingDeleteId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">Xác nhận xóa danh mục</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Danh mục có dữ liệu con sẽ bị từ chối xóa. Thao tác với danh mục không có con không thể hoàn tác.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelDelete"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                        Hủy
                    </button>
                    <button type="button" wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
