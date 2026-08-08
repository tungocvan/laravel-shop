@extends('Admin::layouts.master')

@section('title', 'Chi tiết bài Facebook')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @livewire('facebook.posts.detail', ['id' => $id])
    </div>
@endsection
