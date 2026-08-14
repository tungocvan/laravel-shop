<div class="space-y-4">
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-gray-700">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-indigo-500">Knowledge Discovery</p>
                <h2 class="mt-1 text-base font-bold text-gray-900 dark:text-gray-100">Tìm kiếm Knowledge Base</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tìm theo tiêu đề, tên file, mô tả và nội dung Markdown.</p>
            </div>
            <div class="w-full lg:max-w-xl">
                <label for="ebook-search" class="sr-only">Tìm kiếm Ebook</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">⌕</span>
                    <input id="ebook-search" type="search" wire:model.live.debounce.350ms="search"
                        class="block w-full rounded-xl border-0 bg-white py-3 pl-9 pr-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600"
                        placeholder="Tìm Livewire upload, Docker, migration...">
                </div>
            </div>
        </div>

        <div class="p-5">
            <div wire:loading wire:target="search" class="rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Đang tìm kiếm...</div>

            @if (trim($search) !== '')
                <div wire:loading.remove wire:target="search" class="space-y-3">
                    @forelse ($results as $result)
                        <article class="rounded-xl border border-gray-200 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/30 dark:border-gray-700 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/20">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.ebook.document.show', $result['id']) }}" class="text-sm font-bold text-gray-900 transition hover:text-indigo-700 dark:text-gray-100 dark:hover:text-indigo-300">
                                        {{ $result['title'] }}
                                    </a>
                                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if ($result['folder']) <span>▣ {{ $result['folder'] }}</span> @endif
                                        <span>{{ $result['file_name'] }}</span>
                                        <span class="font-medium text-indigo-600 dark:text-indigo-400">Khớp: {{ implode(', ', $result['matched']) }}</span>
                                    </div>
                                    @if ($result['snippet'])
                                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $result['snippet'] }}</p>
                                    @endif
                                </div>
                                <button type="button" wire:click="toggleFavorite({{ $result['id'] }})" wire:loading.attr="disabled"
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    {{ $result['is_favorite'] ? '★ Yêu thích' : '☆ Yêu thích' }}
                                </button>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Không tìm thấy tài liệu phù hợp</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Thử từ khóa ngắn hơn hoặc tìm theo tên file/thư mục.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-center dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Bắt đầu bằng một từ khóa</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Search content có giới hạn an toàn theo cấu hình Ebook.</p>
                </div>
            @endif
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">★ Tài liệu yêu thích</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Danh sách favorite dùng chung trong Phase 1.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ $favorites->count() }}</span>
                </div>
            </div>
            <div class="max-h-[36vh] divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
                @forelse ($favorites as $document)
                    <a href="{{ route('admin.ebook.document.show', $document->id) }}" class="block px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $document->title }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $document->folder?->name }} · {{ $document->file_name }}</div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Chưa có tài liệu yêu thích.</div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">◷ Đã xem gần đây</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Riêng cho Admin đang đăng nhập.</p>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">{{ $recents->count() }}</span>
                </div>
            </div>
            <div class="max-h-[36vh] divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
                @forelse ($recents as $recent)
                    <a href="{{ route('admin.ebook.document.show', $recent->document->id) }}" class="block px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $recent->document->title }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $recent->document->folder?->name }} · {{ $recent->viewed_at?->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Chưa có lịch sử xem tài liệu.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
