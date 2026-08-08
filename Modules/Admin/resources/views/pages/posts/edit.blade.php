@extends('Admin::layouts.master')

@section('title', 'Chỉnh sửa bài viết')

@section('content')
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-gray-800">Chỉnh sửa bài viết</h2>
    </div>
    @livewire('admin.posts.post-form', ['id' => $id])
@endsection