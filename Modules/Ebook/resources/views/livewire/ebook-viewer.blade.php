<div class="w-full space-y-5">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-4 dark:border-slate-700 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.ebook.index') }}"
               class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                Ebook Knowledge Base
            </a>

            <h1 class="mt-1.5 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-[1.75rem]">
                {{ $document->title }}
            </h1>

            <nav class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.ebook.index') }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Ebook</a>
                @foreach ($breadcrumbs as $item)
                    <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">/</span>
                    <span>{{ $item['name'] }}</span>
                @endforeach
                <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">/</span>
                <span class="max-w-full truncate font-medium text-slate-700 dark:text-slate-200">{{ $document->title }}</span>
            </nav>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button"
                    wire:click="toggleFavorite"
                    wire:loading.attr="disabled"
                    wire:target="toggleFavorite"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3.5 py-2 text-sm font-semibold text-amber-700 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-60 dark:border-amber-700 dark:bg-slate-900 dark:text-amber-300 dark:hover:bg-amber-950/20">
                <span wire:loading.remove wire:target="toggleFavorite">{{ $document->is_favorite ? '★ Đã yêu thích' : '☆ Yêu thích' }}</span>
                <span wire:loading wire:target="toggleFavorite">Đang lưu...</span>
            </button>

            <button type="button"
                    wire:click="toggleReadingMode"
                    wire:loading.attr="disabled"
                    wire:target="toggleReadingMode"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <span wire:loading.remove wire:target="toggleReadingMode">{{ $readingMode ? 'Thoát chế độ đọc' : 'Chế độ đọc' }}</span>
                <span wire:loading wire:target="toggleReadingMode">Đang chuyển...</span>
            </button>
        </div>
    </header>

    @if ($readingMode)
        <main class="mx-auto w-full max-w-5xl min-w-0">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/5 dark:border-slate-700 dark:bg-slate-900">
                <div class="ebook-markdown mx-auto max-w-4xl overflow-x-auto px-5 py-7 text-slate-800 dark:text-slate-200 sm:px-8 sm:py-9 lg:px-10 lg:py-10">
                    {!! $html !!}
                </div>
            </article>
        </main>
    @else
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[230px_minmax(0,1fr)_270px] 2xl:grid-cols-[240px_minmax(0,1fr)_290px]">
            <aside class="min-w-0">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-950/5 dark:border-slate-700 dark:bg-slate-900 xl:sticky xl:top-4">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Tài liệu</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Điều hướng theo thư mục.</p>
                    </div>
                    <div class="max-h-[calc(100dvh-15rem)] overflow-y-auto p-3.5 xl:min-h-[18rem]">
                        @include('Ebook::livewire.partials.navigation-node', ['nodes' => $tree, 'documentId' => $documentId])
                    </div>
                </div>
            </aside>

            <main class="min-w-0">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/5 dark:border-slate-700 dark:bg-slate-900">
                    <div class="ebook-markdown mx-auto max-w-4xl overflow-x-auto px-5 py-7 text-slate-800 dark:text-slate-200 sm:px-8 sm:py-9 lg:px-10 lg:py-10">
                        {!! $html !!}
                    </div>
                </article>
            </main>

            <aside class="min-w-0">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-950/5 dark:border-slate-700 dark:bg-slate-900 xl:sticky xl:top-4">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">Mục lục</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Đi tới nhanh từng phần.</p>
                    </div>
                    <nav class="max-h-[calc(100dvh-15rem)] overflow-y-auto p-3" aria-label="Mục lục tài liệu">
                        @forelse ($toc as $item)
                            <a href="#{{ $item['id'] }}"
                               class="block rounded-lg py-1.5 pr-2 text-[13px] leading-5 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                               style="padding-left: {{ 0.6 + min(3, max(0, $item['level'] - 1)) * 0.55 }}rem;">
                                {{ $item['title'] }}
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-3 py-5 text-center text-xs leading-5 text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Tài liệu chưa có heading.
                            </div>
                        @endforelse
                    </nav>
                </div>
            </aside>
        </div>
    @endif
</div>
