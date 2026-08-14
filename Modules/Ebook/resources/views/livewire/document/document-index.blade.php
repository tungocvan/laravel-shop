<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <section class="xl:col-span-5 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $documentId ? 'Chỉnh sửa tài liệu' : 'Tạo tài liệu' }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nội dung Markdown được lưu trên filesystem, metadata được quản lý trong database.</p>
            </div>
            @if ($documentId)
                <button type="button" wire:click="resetForm"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    + Tạo mới
                </button>
            @endif
        </div>

        <div class="p-5 space-y-6">
            @if (session()->has('ebook_document_success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('ebook_document_success') }}
                </div>
            @endif

            <form wire:submit.prevent="save" class="space-y-5">
                <div>
                    <label for="ebook-document-folder" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Thư mục <span class="text-red-500">*</span></label>
                    <select id="ebook-document-folder" wire:model="folderId"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                        <option value="">-- Chọn thư mục --</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                    @error('folderId') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="ebook-document-title" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Tiêu đề <span class="text-red-500">*</span></label>
                        <input id="ebook-document-title" type="text" wire:model="title"
                            class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                            placeholder="Ví dụ: Livewire Upload">
                        @error('title') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ebook-document-slug" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Slug</label>
                        <input id="ebook-document-slug" type="text" wire:model="slug"
                            class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                            placeholder="Để trống để tự tạo">
                        @error('slug') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="ebook-document-description" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Mô tả</label>
                    <textarea id="ebook-document-description" rows="2" wire:model="description"
                        class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                        placeholder="Tóm tắt ngắn nội dung tài liệu"></textarea>
                    @error('description') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="ebook-document-content" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Markdown</label>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Hỗ trợ H1–H6, table, code block, task list</span>
                    </div>
                    <textarea id="ebook-document-content" rows="14" wire:model="content" spellcheck="false"
                        class="mt-2 block w-full rounded-lg border-0 bg-gray-950 px-4 py-3 font-mono text-sm leading-6 text-gray-100 shadow-sm ring-1 ring-inset ring-gray-700 placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                        placeholder="# Tiêu đề tài liệu"></textarea>
                    @error('content') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="ebook-document-order" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Thứ tự</label>
                        <input id="ebook-document-order" type="number" min="0" wire:model="sortOrder"
                            class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                        @error('sortOrder') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Kích hoạt</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Cho phép tài liệu xuất hiện trong Ebook.</span>
                            </span>
                            <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-700">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $documentId ? 'Lưu thay đổi' : 'Tạo tài liệu' }}</span>
                        <span wire:loading wire:target="save">Đang lưu...</span>
                    </button>
                    @if ($documentId)
                        <button type="button" wire:click="resetForm"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Hủy chỉnh sửa
                        </button>
                    @endif
                </div>
            </form>

            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Upload Markdown</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Chọn thư mục ở trên rồi tải file <code>.md</code>. Tiêu đề sẽ ưu tiên lấy từ H1 đầu tiên.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input type="file" wire:model="upload" accept=".md,text/markdown,text/plain"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-300">
                    <button type="button" wire:click="uploadMarkdown" wire:loading.attr="disabled" wire:target="uploadMarkdown,upload"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 disabled:opacity-60 dark:border-indigo-800 dark:bg-gray-800 dark:text-indigo-300 dark:hover:bg-indigo-950/30">
                        <span wire:loading.remove wire:target="uploadMarkdown">Upload</span>
                        <span wire:loading wire:target="uploadMarkdown">Đang upload...</span>
                    </button>
                </div>
                @error('upload') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="xl:col-span-7 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Tài liệu Markdown</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Danh sách được phân trang để giữ màn hình quản trị gọn và ổn định.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tiêu đề</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Thư mục</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">File</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($documents as $document)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $document->title }}</div>
                                @if ($document->description)
                                    <div class="mt-1 max-w-xs truncate text-xs text-gray-500 dark:text-gray-400">{{ $document->description }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $document->folder?->name }}</td>
                            <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $document->file_name }}</code></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.ebook.document.show', $document) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Xem</a>
                                    <button type="button" wire:click="edit({{ $document->id }})" wire:loading.attr="disabled" wire:target="edit"
                                        class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-60 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">Sửa</button>
                                    <button type="button" wire:click="delete({{ $document->id }})" wire:confirm="Xóa tài liệu này?" wire:loading.attr="disabled" wire:target="delete"
                                        class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-60 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">Xóa</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Chưa có tài liệu Markdown</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tạo tài liệu mới hoặc upload file <code>.md</code> từ biểu mẫu bên trái.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">{{ $documents->links() }}</div>
        @endif
    </section>
</div>
