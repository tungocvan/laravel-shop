@extends('Admin::layouts.master')

@section('title', 'Ebook Knowledge Base')

@section('content')
    <div x-data="{ workspace: 'documents', mobileNav: false }" class="w-full space-y-4">
        <header class="flex flex-col gap-3 border-b border-gray-200 pb-4 dark:border-gray-700 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Knowledge Base</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-gray-100">Ebook</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                    Workspace quản trị tài liệu Markdown, thư mục, tìm kiếm và đồng bộ filesystem.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('ebook.create')
                    <button type="button"
                            @click="workspace = 'documents'; mobileNav = false; $nextTick(() => Livewire.dispatch('ebook-start-new-document'))"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        + Tài liệu mới
                    </button>
                @endcan
                <a href="{{ route('admin.ebook.index') }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    ↻ Làm mới
                </a>
            </div>
        </header>

        <button type="button" @click="mobileNav = !mobileNav"
                class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 lg:hidden">
            <span>☰ Chức năng Ebook</span><span x-text="mobileNav ? '▴' : '▾'"></span>
        </button>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[230px_minmax(0,1fr)]">
            <aside :class="mobileNav ? 'block' : 'hidden'" class="lg:block">
                <div class="rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:sticky lg:top-4">
                    <div class="px-3 pb-2 pt-2 text-[11px] font-bold uppercase tracking-[0.15em] text-gray-400">Workspace</div>

                    <button type="button" @click="workspace = 'documents'; mobileNav = false"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition"
                            :class="workspace === 'documents' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                        <span class="w-5 text-center">✎</span>
                        <span class="flex-1">Tài liệu</span>
                        <span x-show="workspace === 'documents'" class="text-[10px] font-bold uppercase tracking-wide text-indigo-400">Đang mở</span>
                    </button>

                    <button type="button" @click="workspace = 'folders'; mobileNav = false"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition"
                            :class="workspace === 'folders' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                        <span class="w-5 text-center">▣</span>
                        <span class="flex-1">Thư mục</span>
                    </button>

                    <button type="button" @click="workspace = 'search'; mobileNav = false"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition"
                            :class="workspace === 'search' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                        <span class="w-5 text-center">⌕</span>
                        <span class="flex-1">Tìm kiếm</span>
                    </button>

                    @can('ebook.sync')
                        <button type="button" @click="workspace = 'sync'; mobileNav = false"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition"
                                :class="workspace === 'sync' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                            <span class="w-5 text-center">↻</span>
                            <span class="flex-1">Scan & Sync</span>
                        </button>
                    @endcan

                    <div class="my-2 border-t border-gray-100 dark:border-gray-700"></div>
                    <div class="px-3 py-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        <strong class="font-semibold text-gray-600 dark:text-gray-300">Tài liệu</strong> là workspace soạn thảo/danh sách. Dùng <strong>+ Tài liệu mới</strong> khi muốn khởi tạo nội dung mới.
                    </div>
                </div>
            </aside>

            <main class="min-w-0">
                <div x-show="workspace === 'documents'" x-cloak>
                    @livewire('ebook.document.document-index')
                </div>

                <div x-show="workspace === 'folders'" x-cloak>
                    @livewire('ebook.folder.folder-index')
                </div>

                <div x-show="workspace === 'search'" x-cloak>
                    @livewire('ebook.ebook-search')
                </div>

                @can('ebook.sync')
                    <div x-show="workspace === 'sync'" x-cloak>
                        @livewire('ebook.ebook-sync-panel')
                    </div>
                @endcan
            </main>
        </div>
    </div>
@endsection
