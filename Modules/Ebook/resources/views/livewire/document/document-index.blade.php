<div class="space-y-4">
    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="showEditor"
                    class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold transition {{ $workspace === 'editor' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                ✎ Soạn thảo
            </button>
            <button type="button" wire:click="showList"
                    class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold transition {{ $workspace === 'list' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                ☷ Danh sách
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($documentId)
                <a href="{{ route('admin.ebook.document.show', $documentId) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    👁 Xem tài liệu
                </a>
            @endif
            <button type="button" wire:click="resetForm"
                    class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">
                + Tài liệu mới
            </button>
        </div>
    </div>

    @if (session()->has('ebook_document_success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('ebook_document_success') }}
        </div>
    @endif

    @if ($workspace === 'editor')
        <section x-data="{
                    metadataOpen: false,
                    editorFullscreen: false,
                    insertText(text) {
                        const el = this.$refs.editor;
                        if (!el) return;
                        const start = el.selectionStart;
                        const end = el.selectionEnd;
                        el.setRangeText(text, start, end, 'end');
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.focus();
                    },
                    wrap(before, after = before, placeholder = 'nội dung') {
                        const el = this.$refs.editor;
                        if (!el) return;
                        const start = el.selectionStart;
                        const end = el.selectionEnd;
                        const selected = el.value.slice(start, end) || placeholder;
                        el.setRangeText(before + selected + after, start, end, 'select');
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.focus();
                    },
                    prefix(prefix) {
                        const el = this.$refs.editor;
                        if (!el) return;
                        const start = el.selectionStart;
                        const end = el.selectionEnd;
                        const before = el.value.slice(0, start);
                        const selected = el.value.slice(start, end) || 'nội dung';
                        const lineStart = before.lastIndexOf('\n') + 1;
                        const replacement = selected.split('\n').map(line => prefix + line).join('\n');
                        el.setRangeText(replacement, lineStart, end, 'select');
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.focus();
                    },
                    async toggleEditorFullscreen() {
                        const target = this.$refs.editorShell;
                        if (!document.fullscreenElement) await target.requestFullscreen();
                        else await document.exitFullscreen();
                    }
                 }"
                 x-init="document.addEventListener('fullscreenchange', () => editorFullscreen = !!document.fullscreenElement)"
                 x-ref="editorShell"
                 class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                 :class="editorFullscreen ? 'h-screen overflow-y-auto rounded-none border-0 p-4 sm:p-6' : ''">

            <form wire:submit.prevent="save">
                <div class="border-b border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-5">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div>
                            <label for="ebook-document-title" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tiêu đề tài liệu</label>
                            <input id="ebook-document-title" type="text" wire:model="title"
                                   class="block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                                   placeholder="Ví dụ: Laravel Deployment Guide">
                            @error('title') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ebook-document-folder" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Thư mục</label>
                            <select id="ebook-document-folder" wire:model="folderId"
                                    class="block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                                <option value="">-- Chọn thư mục --</option>
                                @foreach ($folders as $folder)
                                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                @endforeach
                            </select>
                            @error('folderId') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="button" @click="metadataOpen = !metadataOpen"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-gray-600 transition hover:text-indigo-700 dark:text-gray-300 dark:hover:text-indigo-300">
                        <span x-text="metadataOpen ? '▾' : '▸'"></span>
                        Thông tin tài liệu & Upload
                    </button>

                    <div x-cloak x-show="metadataOpen" x-collapse class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                            <div class="lg:col-span-4">
                                <label for="ebook-document-slug" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Slug</label>
                                <input id="ebook-document-slug" type="text" wire:model="slug"
                                       class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                                       placeholder="Để trống để tự tạo">
                            </div>
                            <div class="lg:col-span-6">
                                <label for="ebook-document-description" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Mô tả</label>
                                <input id="ebook-document-description" type="text" wire:model="description"
                                       class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                                       placeholder="Tóm tắt ngắn nội dung tài liệu">
                            </div>
                            <div class="lg:col-span-2">
                                <label for="ebook-document-order" class="block text-sm font-semibold text-gray-900 dark:text-gray-100">Thứ tự</label>
                                <input id="ebook-document-order" type="number" min="0" wire:model="sortOrder"
                                       class="mt-2 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600">
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <input type="file" wire:model="upload" accept=".md,text/markdown,text/plain"
                                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-300">
                                    <button type="button" wire:click="uploadMarkdown" wire:loading.attr="disabled" wire:target="uploadMarkdown,upload"
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 disabled:opacity-60 dark:border-indigo-800 dark:bg-gray-800 dark:text-indigo-300">
                                        Upload .md
                                    </button>
                                </div>
                                @error('upload') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                                <span><span class="block text-sm font-semibold">Kích hoạt</span><span class="block text-xs text-gray-500">Hiển thị trong Ebook.</span></span>
                                <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/70">
                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex flex-wrap items-center gap-1" role="toolbar" aria-label="Thanh công cụ Markdown">
                            <button type="button" @click="wrap('**', '**', 'in đậm')" class="ebook-md-tool font-bold" title="In đậm">B</button>
                            <button type="button" @click="wrap('*', '*', 'in nghiêng')" class="ebook-md-tool italic" title="In nghiêng">I</button>
                            <button type="button" @click="wrap('~~', '~~', 'gạch ngang')" class="ebook-md-tool line-through" title="Gạch ngang">S</button>
                            <span class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600"></span>
                            <button type="button" @click="prefix('# ')" class="ebook-md-tool" title="Heading 1">H1</button>
                            <button type="button" @click="prefix('## ')" class="ebook-md-tool" title="Heading 2">H2</button>
                            <button type="button" @click="prefix('### ')" class="ebook-md-tool" title="Heading 3">H3</button>
                            <span class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600"></span>
                            <button type="button" @click="prefix('- ')" class="ebook-md-tool" title="Danh sách">• List</button>
                            <button type="button" @click="prefix('1. ')" class="ebook-md-tool" title="Danh sách đánh số">1. List</button>
                            <button type="button" @click="prefix('- [ ] ')" class="ebook-md-tool" title="Task list">☑ Task</button>
                            <button type="button" @click="prefix('> ')" class="ebook-md-tool" title="Trích dẫn">❝ Quote</button>
                            <span class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600"></span>
                            <button type="button" @click="wrap('[', '](https://)', 'liên kết')" class="ebook-md-tool" title="Liên kết">🔗 Link</button>
                            <button type="button" @click="insertText('![Mô tả ảnh](images/)')" class="ebook-md-tool" title="Hình ảnh">🖼 Image</button>
                            <button type="button" @click="wrap('`', '`', 'code')" class="ebook-md-tool" title="Inline code">&lt;/&gt;</button>
                            <button type="button" @click="insertText('\n```php\n\n```\n')" class="ebook-md-tool" title="Code block">Code</button>
                            <button type="button" @click="insertText('\n| Cột 1 | Cột 2 |\n|---|---|\n| Giá trị | Giá trị |\n')" class="ebook-md-tool" title="Bảng">Table</button>
                            <button type="button" @click="insertText('\n---\n')" class="ebook-md-tool" title="Đường phân cách">— HR</button>
                            <span class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600"></span>
                            <button type="button" @click="$refs.editor?.focus(); document.execCommand('undo')" class="ebook-md-tool" title="Undo">↶</button>
                            <button type="button" @click="$refs.editor?.focus(); document.execCommand('redo')" class="ebook-md-tool" title="Redo">↷</button>
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 shadow-sm dark:border-gray-600 dark:bg-gray-800" aria-label="Chế độ editor">
                                <button type="button" wire:click="showSource" class="rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $editorMode === 'source' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">Soạn thảo</button>
                                <button type="button" wire:click="showSplit" class="hidden rounded-md px-2.5 py-1.5 text-xs font-semibold md:inline-flex {{ $editorMode === 'split' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">Chia đôi</button>
                                <button type="button" wire:click="showPreview" class="rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $editorMode === 'preview' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">Xem trước</button>
                            </div>
                            <button type="button" @click="toggleEditorFullscreen()" class="ebook-md-tool" title="Toàn màn hình editor">
                                <span x-text="editorFullscreen ? '⊠ Thoát' : '⛶ Editor'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                @if ($editorMode === 'source')
                    <div class="bg-gray-950">
                        <textarea id="ebook-document-content" x-ref="editor" wire:model="content" spellcheck="false"
                                  class="block min-h-[65vh] w-full resize-y border-0 bg-gray-950 px-5 py-5 font-mono text-[14px] leading-7 text-gray-100 outline-none ring-0 placeholder:text-gray-600 focus:ring-0 sm:px-6"
                                  :class="editorFullscreen ? 'min-h-[calc(100vh-15rem)]' : ''"
                                  placeholder="# Tiêu đề tài liệu\n\nBắt đầu soạn Markdown..."></textarea>
                    </div>
                @elseif ($editorMode === 'split')
                    <div class="grid min-h-[65vh] grid-cols-1 md:grid-cols-2">
                        <div class="min-w-0 border-b border-gray-700 bg-gray-950 md:border-b-0 md:border-r">
                            <div class="border-b border-gray-800 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-400">Markdown</div>
                            <textarea id="ebook-document-content" x-ref="editor" wire:model="content" spellcheck="false"
                                      class="block min-h-[61vh] w-full resize-none border-0 bg-gray-950 px-5 py-5 font-mono text-[14px] leading-7 text-gray-100 outline-none ring-0 placeholder:text-gray-600 focus:ring-0"
                                      placeholder="# Tiêu đề tài liệu\n\nBắt đầu soạn Markdown..."></textarea>
                        </div>
                        <div class="min-w-0 bg-white dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:border-gray-700">Preview</div>
                            <div class="ebook-markdown max-h-[61vh] overflow-y-auto px-6 py-5 text-slate-800 dark:text-slate-200">
                                {!! $previewHtml ?: '<p class="text-sm text-gray-400">Chưa có nội dung để xem trước.</p>' !!}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="min-h-[65vh] bg-white dark:bg-gray-900">
                        <div class="border-b border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:border-gray-700">Preview an toàn · cùng renderer với Ebook Viewer</div>
                        <div class="ebook-markdown mx-auto max-w-5xl px-6 py-8 text-slate-800 dark:text-slate-200 sm:px-8 lg:px-10">
                            {!! $previewHtml ?: '<p class="text-sm text-gray-400">Chưa có nội dung để xem trước.</p>' !!}
                        </div>
                    </div>
                @endif

                @error('content') <p class="bg-red-50 px-5 py-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                <div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Markdown thuần · Preview dùng safe renderer · H1–H6 · Table · Task list · Code · Link · Image
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($documentId)
                            <button type="button" wire:click="resetForm"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                Hủy chỉnh sửa
                            </button>
                        @endif
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">{{ $documentId ? 'Lưu thay đổi' : 'Tạo tài liệu' }}</span>
                            <span wire:loading wire:target="save">Đang lưu...</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Tài liệu Markdown</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Danh sách tài liệu đã lưu.</p>
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
                                    @if ($document->description)<div class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $document->description }}</div>@endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $document->folder?->name }}</td>
                                <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $document->file_name }}</code></td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.ebook.document.show', $document) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">Xem</a>
                                        <button type="button" wire:click="edit({{ $document->id }})" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">Sửa</button>
                                        <button type="button" wire:click="delete({{ $document->id }})" wire:confirm="Xóa tài liệu này?" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">Xóa</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">Chưa có tài liệu Markdown.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($documents->hasPages())<div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">{{ $documents->links() }}</div>@endif
        </section>
    @endif

    <style>
        .ebook-md-tool {
            display: inline-flex;
            min-height: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: .5rem;
            padding: .35rem .55rem;
            font-size: .75rem;
            font-weight: 700;
            color: rgb(55 65 81);
            transition: background-color .15s ease, color .15s ease;
        }
        .ebook-md-tool:hover { background: rgb(229 231 235); color: rgb(67 56 202); }
        .dark .ebook-md-tool { color: rgb(209 213 219); }
        .dark .ebook-md-tool:hover { background: rgb(55 65 81); color: rgb(165 180 252); }
    </style>
</div>
