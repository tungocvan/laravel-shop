@extends('Website::layouts.frontend')
{{-- Lưu ý: Title SEO sẽ được handle trong Livewire component hoặc truyền từ controller nếu muốn tối ưu hơn --}}

@section('content')
    @livewire('website.post.post-detail', ['slug' => $slug])
@endsection
