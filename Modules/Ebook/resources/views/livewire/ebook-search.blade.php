<div class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Tìm kiếm Knowledge Base</h2>
                <p class="mt-1 text-sm text-gray-500">Tìm theo tiêu đề, tên file, mô tả và nội dung Markdown.</p>
            </div>
            <div class="w-full sm:max-w-md">
                <label for="ebook-search" class="sr-only">Tìm kiếm Ebook</label>
                <input id="ebook-search" type="search" wire:model.live.debounce.350ms="search"
                    class="block w-full rounded-lg border-0 px-3 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                    placeholder="Ví dụ: Livewire upload, Docker, migration...">
            </div>
        </div>

        <div wire:loading wire:target="search" class="mt-4 text-sm text-gray-500">Đang tìm kiếm...</div>

        @if (trim($search) !== '')
            <div wire:loading.remove wire:target="search" class="mt-5 space-y-3">
                @forelse ($results as $result)
                    <div class="rounded-lg border border-gray-200 p-4 hover:border-indigo-200 hover:bg-indigo-50/30 transition-colors">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <a href="{{ route('admin.ebook.document.show', $result['id']) }}" class="font-semibold text-gray-900 hover:text-indigo-700">
                                    {{ $result['title'] }}
                                </a>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                                    @if ($result['folder']) <span>{{ $result['folder'] }}</span> @endif
                                    <span>{{ $result['file_name'] }}</span>
                                    <span>Khớp: {{ implode(', ', $result['matched']) }}</span>
                                </div>
                                @if ($result['snippet'])
                                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $result['snippet'] }}</p>
                                @endif
                            </div>
                            <button type="button" wire:click="toggleFavorite({{ $result['id'] }})" wire:loading.attr="disabled"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                                {{ $result['is_favorite'] ? '★ Yêu thích' : '☆ Yêu thích' }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                        Không tìm thấy tài liệu phù hợp.
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-bold text-gray-900">★ Tài liệu yêu thích</h3>
                <p class="mt-1 text-xs text-gray-500">Danh sách yêu thích dùng chung trong MVP.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($favorites as $document)
                    <a href="{{ route('admin.ebook.document.show', $document->id) }}" class="block px-5 py-3 hover:bg-gray-50">
                        <div class="text-sm font-semibold text-gray-800">{{ $document->title }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $document->folder?->name }} · {{ $document->file_name }}</div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500">Chưa có tài liệu yêu thích.</div>
                @endforelse
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-bold text-gray-900">Đã xem gần đây</h3>
                <p class="mt-1 text-xs text-gray-500">Riêng cho tài khoản Admin đang đăng nhập.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($recents as $recent)
                    <a href="{{ route('admin.ebook.document.show', $recent->document->id) }}" class="block px-5 py-3 hover:bg-gray-50">
                        <div class="text-sm font-semibold text-gray-800">{{ $recent->document->title }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $recent->document->folder?->name }} · {{ $recent->viewed_at?->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500">Chưa có lịch sử xem tài liệu.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
