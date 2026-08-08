@extends('Admin::layouts.master')

@section('title', 'Quản lý bài viết')

@section('content')   
    @livewire('admin.posts.post-table')
@endsection