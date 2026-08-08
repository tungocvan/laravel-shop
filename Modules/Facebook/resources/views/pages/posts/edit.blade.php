@extends('Admin::layouts.master')

@section('title', 'Sửa bài Facebook')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Sửa bài Facebook</h1>
            <p class="mt-1 text-sm text-gray-500">Chỉnh sửa bản nháp hoặc bài chưa hoàn tất xử lý.</p>
        </div>

        @livewire('facebook.posts.form', ['id' => $id])
    </div>
@endsection
