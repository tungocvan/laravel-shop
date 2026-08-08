@extends('Website::layouts.frontend')
@section('content')
    <div class="container mx-auto py-10 px-4">
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Sidebar --}}
            <div class="lg:w-1/4">
                @include('Website::partials.account-sidebar')
            </div>

            {{-- Nội dung chính --}}
            <div class="lg:w-3/4 space-y-8">
                {{-- 1. Hồ sơ cá nhân --}}
                @livewire('website.account.profile.user-profile')

                {{-- 2. Sổ địa chỉ (Đặt ở đây để thẳng hàng) --}}
                @livewire('website.account.profile.user-address')
            </div>
        </div>
    </div>
@endsection
