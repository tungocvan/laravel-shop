<div class="w-full space-y-4"
     x-data="{
        pickerOpen: false,
        tocOpen: false,
        query: '',
        fullscreen: false,
        documents: @js($documentPicker),
        get filteredDocuments() {
            const needle = this.query.trim().toLocaleLowerCase();
            if (!needle) return this.documents;
            return this.documents.filter(item => (`${item.title} ${item.folder}`).toLocaleLowerCase().includes(needle));
        },
        openDocument(id) {
            window.location.href = @js(route('admin.ebook.index')) + '/document/' + id;
        },
        async toggleFullscreen() {
            const target = this.$refs.reader;
            if (!document.fullscreenElement) {
                await target.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        }
     }"
     x-init="document.addEventListener('fullscreenchange', () => fullscreen = !!document.fullscreenElement)">

    <header class="border-b border-slate-200 pb-4 dark:border-slate-700">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.ebook.index') }}"
                       class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        Ebook Knowledge Base
                    </a>

                    <div class="relative w-full max-w-md sm:w-auto sm:min-w-80" @click.outside="pickerOpen = false">
                        <button type="button" @click="pickerOpen = !pickerOpen; $nextTick(() => pickerOpen && $refs.pickerSearch.focus())"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-600 shadow-sm transition hover:border-indigo-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300">
                            <span class="truncate">⌕ Tìm hoặc chọn tài liệu...</span><span aria-hidden="true">⌄</span>
                        </button>
                        <div x-cloak x-show="pickerOpen" x-transition
                             class="absolute left-0 z-50 mt-2 w-full min-w-[20rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900 sm:w-[28rem]">
                            <div class="border-b border-slate-100 p-3 dark:border-slate-800">
                                <input x-ref="pickerSearch" x-model="query" type="search" placeholder="Tìm theo tiêu đề hoặc thư mục..."
                                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-950">
                            </div>
                            <div class="max-h-80 overflow-y-auto p-2">
                                <template x-for="item in filteredDocuments" :key="item.id">
                                    <button type="button" @click="openDocument(item.id)"
                                            class="flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-950/30">
                                        <span class="min-w-0"><span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.title"></span><span class="mt-0.5 block truncate text-xs text-slate-500" x-text="item.folder"></span></span>
                                        <span x-show="item.favorite" class="text-amber-500">★</span>
                                    </button>
                                </template>
                                <div x-show="filteredDocuments.length === 0" class="px-3 py-6 text-center text-sm text-slate-500">Không tìm thấy tài liệu.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-[1.75rem]">{{ $document->title }}</h1>
                <nav class="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
                    <a href="{{ route('admin.ebook.index') }}" class="transition hover:text-indigo-600">Ebook</a>
                    @foreach ($breadcrumbs as $item)<span class="text-slate-300">/</span><span>{{ $item['name'] }}</span>@endforeach
                    <span class="text-slate-300">/</span><span class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $document->title }}</span>
                </nav>

                @if ($adjacent['previous'] || $adjacent['next'])
                    <nav class="mt-3 flex flex-wrap items-center gap-2" aria-label="Điều hướng tài liệu nhanh">
                        @if ($adjacent['previous'])
                            <a href="{{ route('admin.ebook.document.show', $adjacent['previous']['id']) }}"
                               class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                <span aria-hidden="true">←</span>
                                <span class="min-w-0"><span class="block text-[11px] uppercase tracking-wide text-slate-400">Trước</span><span class="block max-w-64 truncate font-medium">{{ $adjacent['previous']['title'] }}</span></span>
                            </a>
                        @endif
                        @if ($adjacent['next'])
                            <a href="{{ route('admin.ebook.document.show', $adjacent['next']['id']) }}"
                               class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                <span class="min-w-0 text-right"><span class="block text-[11px] uppercase tracking-wide text-slate-400">Tiếp</span><span class="block max-w-64 truncate font-medium">{{ $adjacent['next']['title'] }}</span></span>
                                <span aria-hidden="true">→</span>
                            </a>
                        @endif
                    </nav>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button type="button" class="lg:hidden inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold dark:border-slate-600 dark:bg-slate-900" @click="tocOpen = true">☰ Mục lục</button>
                <button type="button" wire:click="toggleFavorite" wire:loading.attr="disabled" wire:target="toggleFavorite"
                        class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-amber-700 shadow-sm hover:bg-amber-50 dark:border-amber-700 dark:bg-slate-900 dark:text-amber-300">
                    <span wire:loading.remove wire:target="toggleFavorite">{{ $document->is_favorite ? '★ Đã yêu thích' : '☆ Yêu thích' }}</span><span wire:loading wire:target="toggleFavorite">Đang lưu...</span>
                </button>
                <button type="button" wire:click="toggleReadingMode" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm dark:border-slate-600 dark:bg-slate-900">
                    {{ $readingMode ? 'Thoát chế độ đọc' : 'Chế độ đọc' }}
                </button>
                <button type="button" @click="toggleFullscreen()" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm dark:border-slate-600 dark:bg-slate-900">
                    <span x-text="fullscreen ? '⊠ Thoát toàn màn hình' : '⛶ Toàn màn hình'"></span>
                </button>
            </div>
        </div>
    </header>

    <div x-cloak x-show="tocOpen" class="fixed inset-0 z-[70] lg:hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-950/45" @click="tocOpen = false"></div>
        <aside class="absolute inset-y-0 left-0 w-[min(86vw,22rem)] overflow-y-auto bg-white shadow-2xl dark:bg-slate-900">
            <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                <strong>Mục lục</strong><button type="button" @click="tocOpen = false" class="rounded-lg px-3 py-2">✕</button>
            </div>
            <nav class="p-3">
                @forelse ($toc as $item)
                    <a href="#{{ $item['id'] }}" @click="tocOpen = false" class="block rounded-lg py-2 pr-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300" style="padding-left: {{ 0.7 + min(3, max(0, $item['level'] - 1)) * 0.65 }}rem;">{{ $item['title'] }}</a>
                @empty
                    <p class="p-4 text-sm text-slate-500">Tài liệu chưa có heading.</p>
                @endforelse
            </nav>
        </aside>
    </div>

    <div x-ref="reader" class="bg-slate-50 dark:bg-slate-950" :class="fullscreen ? 'h-screen w-screen overflow-y-auto p-4 sm:p-6' : ''">
        <div :class="fullscreen ? 'mx-auto w-full max-w-screen-2xl' : ''">
            <div x-show="fullscreen" class="mb-4 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="min-w-0"><div class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Ebook</div><div class="truncate font-bold">{{ $document->title }}</div></div>
                <button type="button" @click="toggleFullscreen()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-600">✕ Thoát</button>
            </div>

            @if ($readingMode)
                <main class="mx-auto w-full" :class="fullscreen ? 'max-w-screen-2xl' : 'max-w-5xl'">
                    <article class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="ebook-markdown mx-auto overflow-x-auto px-5 py-7 text-slate-800 dark:text-slate-200 sm:px-8 sm:py-9 lg:px-10 lg:py-10" :class="fullscreen ? 'max-w-6xl' : 'max-w-5xl'">{!! $html !!}</div>
                    </article>
                </main>
            @else
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[240px_minmax(0,1fr)] 2xl:grid-cols-[250px_minmax(0,1fr)]" :class="fullscreen ? '!grid-cols-1' : ''">
                    <aside class="hidden min-w-0 lg:block" :class="fullscreen ? '!hidden' : ''">
                        <div class="sticky top-4 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="text-sm font-bold">Mục lục</h2><p class="mt-1 text-xs text-slate-500">Đi tới nhanh từng phần.</p></div>
                            <nav class="max-h-[calc(100dvh-15rem)] overflow-y-auto p-3">
                                @forelse ($toc as $item)
                                    <a href="#{{ $item['id'] }}" class="block rounded-lg py-1.5 pr-2 text-[13px] leading-5 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300" style="padding-left: {{ 0.6 + min(3, max(0, $item['level'] - 1)) * 0.55 }}rem;">{{ $item['title'] }}</a>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-500">Tài liệu chưa có heading.</div>
                                @endforelse
                            </nav>
                        </div>
                    </aside>
                    <main class="min-w-0 w-full">
                        <article class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="ebook-markdown mx-auto overflow-x-auto px-5 py-7 text-slate-800 dark:text-slate-200 sm:px-8 sm:py-9 lg:px-10 lg:py-10" :class="fullscreen ? 'max-w-6xl' : 'max-w-5xl'">{!! $html !!}</div>
                        </article>
                    </main>
                </div>
            @endif
        </div>
    </div>

    <nav class="grid grid-cols-1 gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:grid-cols-2" aria-label="Tài liệu trước và sau">
        @if ($adjacent['previous'])
            <a href="{{ route('admin.ebook.document.show', $adjacent['previous']['id']) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-900"><span class="text-xs text-slate-500">← Tài liệu trước</span><strong class="mt-1 block text-sm">{{ $adjacent['previous']['title'] }}</strong></a>
        @else<div></div>@endif
        @if ($adjacent['next'])
            <a href="{{ route('admin.ebook.document.show', $adjacent['next']['id']) }}" class="rounded-xl border border-slate-200 bg-white p-4 text-right shadow-sm transition hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-900"><span class="text-xs text-slate-500">Tài liệu tiếp theo →</span><strong class="mt-1 block text-sm">{{ $adjacent['next']['title'] }}</strong></a>
        @endif
    </nav>

    <button type="button" x-data="{ visible: false }" x-init="window.addEventListener('scroll', () => visible = window.scrollY > 500)" x-show="visible" x-transition @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            class="fixed bottom-5 right-5 z-40 rounded-full border border-slate-300 bg-white px-3.5 py-3 text-sm font-bold shadow-lg dark:border-slate-600 dark:bg-slate-900" aria-label="Lên đầu trang">↑</button>
</div>
