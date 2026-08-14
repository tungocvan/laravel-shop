@extends('Admin::layouts.master')

@section('title', 'Ebook Knowledge Base')

@section('content')
    <div class="container-fluid py-3">
        <div class="mb-3">
            <h1 class="h4 mb-1">Ebook Knowledge Base</h1>
            <p class="text-muted mb-0">Quản lý thư mục và tài liệu Markdown. Viewer/TOC sẽ được bổ sung ở MR-4.</p>
        </div>

        <div class="mb-4">
            @livewire('ebook.folder.folder-index')
        </div>

        @livewire('ebook.document.document-index')
    </div>
@endsection
