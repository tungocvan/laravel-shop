@extends('Website::layouts.frontend')
@section('title', 'Blog')

@section('content')
    {{-- Truyền biến categorySlug vào Component --}}
    @livewire('website.post.post-list', ['categorySlug' => $categorySlug ?? null])
@endsection
