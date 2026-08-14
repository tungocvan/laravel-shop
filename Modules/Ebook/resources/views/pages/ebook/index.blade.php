@extends('Admin::layouts.master')

@section('title', 'Ebook Knowledge Base')

@section('content')
    <div class="container-fluid py-3">
        <div class="mb-3">
            <h1 class="h4 mb-1">Ebook Knowledge Base</h1>
            <p class="text-muted mb-0">Quản lý cấu trúc thư mục Markdown. Document/Viewer sẽ được bổ sung ở các MR tiếp theo.</p>
        </div>

        @livewire('ebook.folder.folder-index')
    </div>
@endsection
