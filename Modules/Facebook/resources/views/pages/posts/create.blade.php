@extends('Admin::layouts.master')

@section('title', 'Tạo bài Facebook')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tạo bài Facebook</h1>
            <p class="mt-1 text-sm text-gray-500">Soạn nội dung, ảnh hoặc liên kết để lưu nháp, đăng ngay hoặc lên lịch.</p>
        </div>

        @livewire('facebook.posts.form')
    </div>
@endsection
