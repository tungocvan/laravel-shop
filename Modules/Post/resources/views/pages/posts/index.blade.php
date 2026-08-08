@extends('Admin::layouts.master')

@section('title', 'Quản lý bài viết')

@section('content')
    @livewire('post.posts.post-table')
@endsection
