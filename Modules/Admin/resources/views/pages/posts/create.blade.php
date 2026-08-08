@extends('Admin::layouts.master')

@section('title', 'Viết bài mới')
@section('css')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
@endsection
@section('content')
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-gray-800">Thêm bài viết mới</h2>
    </div>
    @livewire('admin.posts.post-form')
@endsection
