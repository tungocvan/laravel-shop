<div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(260px,0.8fr)_minmax(0,1.35fr)]">
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-indigo-500">Library Structure</p>
                <h2 class="mt-1 text-base font-bold text-gray-900 dark:text-gray-100">Cây thư mục</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Điều hướng cấu trúc nhiều cấp của Ebook.</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                {{ $tree->count() }} gốc
            </span>
        </div>

        <div class="max-h-[68vh] overflow-y-auto p-4 sm:p-5">
            @if (session()->has('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @forelse ($tree as $folder)
                @include('Ebook::livewire.folder.partials.folder-node', ['folder' => $folder, 'level' => 0])
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-lg text-gray-500 dark:bg-gray-700 dark:text-gray-300">▣</div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Chưa có thư mục Ebook</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tạo thư mục đầu tiên ở workspace bên cạnh.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-400">Folder Editor</p>
                <h2 class="mt-1 text-base font-bold text-gray-900 dark:text-gray-100">{{ $folderId ? 'Chỉnh sửa thư mục' : 'Tạo thư mục mới' }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tên và vị trí quyết định đường dẫn vật lý trong Ebook storage.</p>
            </div>
            @if ($folderId)
                <button type="button" wire:click="resetForm"
                    class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">
                    + Thư mục mới
                </button>
            @endif
        </div>

        <form wire:submit="save" class="space-y-5 p-5">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label for="ebook-folder-name" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Tên thư mục <span class="text-red-500">*</span></label>
                    <input id="ebook-folder-name" type="text" wire:model="name"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                        placeholder="Ví dụ: Laravel">
                    @error('name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="lg:col-span-4">
                    <label for="ebook-folder-parent" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Thư mục cha</label>
                    <select id="ebook-folder-parent" wire:model="parentId"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                        <option value="">-- Thư mục gốc --</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('parentId') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="lg:col-span-3">
                    <label for="ebook-folder-order" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Thứ tự</label>
                    <input id="ebook-folder-order" type="number" min="0" wire:model="sortOrder"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                    @error('sortOrder') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label for="ebook-folder-slug" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Slug</label>
                    <input id="ebook-folder-slug" type="text" wire:model="slug"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                        placeholder="Để trống để tự tạo">
                    @error('slug') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="lg:col-span-8">
                    <label for="ebook-folder-description" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Mô tả</label>
                    <input id="ebook-folder-description" type="text" wire:model="description"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                        placeholder="Mô tả ngắn mục đích của thư mục">
                    @error('description') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                <span>
                    <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Đang hoạt động</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">Cho phép thư mục xuất hiện trong cây Ebook.</span>
                </span>
                <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            </label>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Không thể xóa thư mục còn tài liệu hoặc thư mục con.</p>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($folderId)
                        <button type="button" wire:click="delete({{ $folderId }})" wire:confirm="Xóa thư mục này?" wire:loading.attr="disabled" wire:target="delete"
                            class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50 disabled:opacity-60 dark:border-red-800 dark:bg-gray-800 dark:text-red-300 dark:hover:bg-red-950/30">
                            Xóa thư mục
                        </button>
                    @endif
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $folderId ? 'Lưu thay đổi' : 'Tạo thư mục' }}</span>
                        <span wire:loading wire:target="save">Đang lưu...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>
</div>
