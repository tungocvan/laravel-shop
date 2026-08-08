@extends('Website::layouts.frontend')
@section('content')

<div class="bg-gray-50 min-h-screen py-12 font-sans">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="mb-8">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-black transition mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Quay lại giỏ hàng
            </a>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Thanh toán an toàn</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            {{-- Cột trái: Form (Chiếm 7 phần) --}}
            <div class="lg:col-span-7">
                @livewire('website.checkout.checkout-form')
            </div>

            {{-- Cột phải: Summary (Chiếm 5 phần) --}}
            <div class="lg:col-span-5">
                @livewire('website.checkout.order-summary')
            </div>
            
        </div>
    </div>
</div>
@endsection
