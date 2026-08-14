@extends('Admin::layouts.master')

@section('title', 'Ebook Knowledge Base')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Knowledge Base</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Ebook</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Quản lý cây thư mục, tài liệu Markdown và mở nội dung trong chế độ đọc có mục lục.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="rounded-full border border-gray-200 bg-white px-3 py-1.5 shadow-sm dark:border-gray-700 dark:bg-gray-800">Markdown filesystem</span>
                <span class="rounded-full border border-gray-200 bg-white px-3 py-1.5 shadow-sm dark:border-gray-700 dark:bg-gray-800">Metadata MySQL</span>
            </div>
        </div>

        @livewire('ebook.folder.folder-index')
        @livewire('ebook.document.document-index')
    </div>
@endsection
