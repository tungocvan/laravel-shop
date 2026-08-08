@extends('Admin::layouts.master')

@section('title', 'Fanpage Facebook')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Fanpage Facebook</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý Fanpage đã cấp quyền, token và trạng thái sử dụng.</p>
            </div>
            <form method="POST" action="{{ route('admin.facebook.sync-pages') }}">
                @csrf
                <button class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Đồng bộ Page</button>
            </form>
        </div>

        @livewire('facebook.pages.index')
    </div>
@endsection
