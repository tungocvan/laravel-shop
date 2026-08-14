<div class="max-w-[1600px] mx-auto px-4 sm:px-6 md:px-8 py-6">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.ebook.index') }}" class="text-xs font-semibold uppercase tracking-wider text-indigo-600 hover:text-indigo-700">Ebook Knowledge Base</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $document->title }}</h1>
            <nav class="mt-2 flex flex-wrap items-center gap-1 text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <a href="{{ route('admin.ebook.index') }}" class="hover:text-indigo-600">Ebook</a>
                @foreach ($breadcrumbs as $item)
                    <span aria-hidden="true">/</span><span>{{ $item['name'] }}</span>
                @endforeach
                <span aria-hidden="true">/</span><span class="font-medium text-gray-700 dark:text-gray-200">{{ $document->title }}</span>
            </nav>
        </div>

        <button type="button" wire:click="toggleReadingMode" wire:loading.attr="disabled" wire:target="toggleReadingMode"
            class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <span wire:loading.remove wire:target="toggleReadingMode">{{ $readingMode ? 'Thoát chế độ đọc' : 'Chế độ đọc' }}</span>
            <span wire:loading wire:target="toggleReadingMode">Đang chuyển...</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 {{ $readingMode ? '' : 'lg:grid-cols-12' }}">
        @unless ($readingMode)
            <aside class="lg:col-span-3">
                <div class="sticky top-4 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Tài liệu</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Điều hướng theo thư mục.</p>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto p-4">
                        @include('Ebook::livewire.partials.navigation-node', ['nodes' => $tree, 'documentId' => $documentId])
                    </div>
                </div>
            </aside>
        @endunless

        <main class="{{ $readingMode ? 'mx-auto w-full max-w-5xl' : 'lg:col-span-7' }} min-w-0">
            <article class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="ebook-markdown overflow-x-auto px-5 py-6 sm:px-8 sm:py-8 text-gray-800 dark:text-gray-200">
                    {!! $html !!}
                </div>
            </article>
        </main>

        @unless ($readingMode)
            <aside class="lg:col-span-2">
                <div class="sticky top-4 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Mục lục</h2>
                    </div>
                    <nav class="max-h-[75vh] overflow-y-auto p-4" aria-label="Mục lục tài liệu">
                        @forelse ($toc as $item)
                            <a href="#{{ $item['id'] }}"
                               class="block rounded-md py-1.5 pr-2 text-sm text-gray-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                               style="padding-left: {{ 0.5 + max(0, $item['level'] - 1) * 0.65 }}rem;">
                                {{ $item['title'] }}
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 px-3 py-5 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">Tài liệu chưa có heading.</div>
                        @endforelse
                    </nav>
                </div>
            </aside>
        @endunless
    </div>
</div>
