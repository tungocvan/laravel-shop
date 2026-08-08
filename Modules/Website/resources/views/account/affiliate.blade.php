@extends('Website::layouts.frontend')

@section('title', 'Đối tác Affiliate')

@section('content')
    <div class="bg-gray-50 min-h-screen py-10">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">

                {{-- SIDEBAR TÀI KHOẢN (Nếu bạn có component sidebar riêng thì include vào đây) --}}
                <div class="lg:w-1/4">
                    @include('Website::partials.account-sidebar')
                </div>

                {{-- NỘI DUNG CHÍNH --}}
                <div class="lg:w-3/4">
                    @livewire('website.account.affiliate.affiliate-dashboard')
                </div>

            </div>
        </div>
    </div>
@endsection
