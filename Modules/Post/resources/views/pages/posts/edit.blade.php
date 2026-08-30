@extends('Admin::layouts.master')

@section('title', 'Chỉnh sửa bài viết')

@section('content')
    @livewire('post.posts.post-form', ['id' => $id])
@endsection
