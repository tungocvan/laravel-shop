@extends('Admin::layouts.master')

@section('title', 'Cấu hình hoa hồng: ' . $product->title)

@section('content')
    <div class="min-h-screen bg-gray-50/50 pb-12">
        {{-- Breadcrumb chuyên nghiệp --}}
        <nav class="flex mb-6 px-4 py-3 bg-white border-b border-gray-100" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-blue-600 transition">Dashboard</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('admin.products.index') }}" class="ml-1 text-sm text-gray-500 hover:text-blue-600 transition md:ml-2">Sản phẩm</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm font-bold text-gray-900 md:ml-2">Cấu hình hoa hồng Hybrid</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Main Content --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Thiết lập Ma trận Hoa hồng</h1>
                <p class="mt-1 text-sm text-gray-500 italic">Quản lý chi tiết mức thưởng cho từng cấp độ và đối tác đặc biệt cho sản phẩm này.</p>
            </div>

            {{-- Gọi Livewire Component --}}
            @livewire('admin.affiliate.commission-matrix', ['productId' => $product->id])
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* Tùy chỉnh thanh cuộn cho bảng nếu cần */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
@endsection