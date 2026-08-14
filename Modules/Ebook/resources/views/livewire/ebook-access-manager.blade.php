<div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 px-4 py-4 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-400">Tài liệu</p>
            <h2 class="mt-1 text-base font-bold text-gray-900 dark:text-gray-100">Chọn tài liệu cần phân quyền</h2>
            <input type="search" wire:model.live.debounce.300ms="documentSearch"
                   class="mt-3 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                   placeholder="Tìm theo tiêu đề hoặc tên file...">
        </div>

        <div class="max-h-[65vh] divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
            @forelse ($documents as $document)
                <button type="button" wire:click="selectDocument({{ $document->id }})"
                        class="block w-full px-4 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $documentId === $document->id ? 'bg-indigo-50 dark:bg-indigo-950/30' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold {{ $documentId === $document->id ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-900 dark:text-gray-100' }}">{{ $document->title }}</div>
                            <div class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $document->folder?->name }} · {{ $document->file_name }}</div>
                        </div>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $document->viewers_count }} user</span>
                    </div>
                </button>
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-500">Không tìm thấy tài liệu.</div>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($selectedDocument)
            <form wire:submit.prevent="save">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600 dark:text-indigo-400">Người được xem</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $selectedDocument->title }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Chỉ những user được tick bên dưới mới thấy tài liệu này. Super Admin luôn được bypass.</p>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Lưu quyền xem</span>
                        <span wire:loading wire:target="save">Đang lưu...</span>
                    </button>
                </div>

                <div class="p-5">
                    @if (session()->has('ebook_access_success'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">{{ session('ebook_access_success') }}</div>
                    @endif

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/20 dark:text-indigo-300">
                        User vẫn cần có permission <code class="font-semibold">ebook.view</code> để vào Ebook. Sau đó tài liệu này chỉ xuất hiện nếu user được chọn ở đây.
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Danh sách user</div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Đã chọn {{ count($viewerIds) }} người.</div>
                        </div>
                        <input type="search" wire:model.live.debounce.300ms="userSearch"
                               class="block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600 sm:max-w-md"
                               placeholder="Tìm theo tên hoặc email...">
                    </div>

                    <div class="mt-4 max-h-[55vh] divide-y divide-gray-100 overflow-y-auto rounded-xl border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                        @forelse ($users as $user)
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <input type="checkbox" wire:model="viewerIds" value="{{ $user->id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                </div>
                            </label>
                        @empty
                            <div class="px-4 py-10 text-center text-sm text-gray-500">Không tìm thấy user.</div>
                        @endforelse
                    </div>
                    @error('viewerIds.*') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </form>
        @else
            <div class="flex min-h-[420px] items-center justify-center p-8 text-center">
                <div>
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">👥</div>
                    <h2 class="mt-4 text-base font-bold text-gray-900 dark:text-gray-100">Chọn một tài liệu</h2>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">Chọn tài liệu ở cột bên trái để thêm hoặc thu hồi quyền xem của từng user.</p>
                </div>
            </div>
        @endif
    </section>
</div>
