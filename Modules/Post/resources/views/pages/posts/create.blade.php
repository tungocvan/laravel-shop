@extends('Admin::layouts.master')

@section('title', 'Viết bài mới')

@section('content')
    @livewire('post.posts.post-form')
@endsection
