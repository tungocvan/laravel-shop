@extends('Admin::layouts.master')

@section('title', 'Bài đăng Facebook')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Bài đăng Facebook</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý bản nháp, lịch đăng, trạng thái đăng và lỗi Meta.</p>
            </div>
            <a href="{{ route('admin.facebook.posts.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Tạo bài</a>
        </div>

        @livewire('facebook.posts.index')
    </div>
@endsection
