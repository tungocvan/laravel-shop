@extends('Admin::layouts.master')

@section('title', 'Facebook Fanpage')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Facebook Fanpage</h1>
                <p class="mt-1 text-sm text-gray-500">Kết nối Fanpage, soạn bài và vận hành đăng bài tự động qua Meta Graph API.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.facebook.posts.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Tạo bài</a>
                <a href="{{ route('admin.facebook.connect') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Kết nối Facebook</a>
            </div>
        </div>

        @livewire('facebook.dashboard.index')
    </div>
@endsection
