@extends('Website::layouts.frontend')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        {{-- Sidebar Menu Tài Khoản (Nếu có) --}}
        <div class="w-full lg:w-1/4">
            @include('Website::partials.account-sidebar')
        </div>

        {{-- Nội dung chính --}}
        <div class="w-full lg:w-3/4">
            @livewire('website.account.wishlist-page')
        </div>
    </div>
</div>
@endsection
