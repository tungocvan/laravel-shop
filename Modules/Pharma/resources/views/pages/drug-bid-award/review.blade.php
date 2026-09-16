@extends('Admin::layouts.master')

@section('title', 'Rà soát liên kết kết quả trúng thầu')

@section('content')
    <div class="mx-auto max-w-screen-2xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @include('Pharma::pages.partials.dashboard-back')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500">Pharma · Bid Intelligence</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Rà soát kết quả trúng thầu</h1>
                <p class="mt-2 max-w-3xl text-sm text-gray-600">Đối chiếu dữ liệu từ Mua sắm công với Medicine, SKU và Package canonical. Các liên kết xác nhận thủ công được bảo vệ khỏi đồng bộ tự động.</p>
            </div>
            <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">Danh sách trúng thầu</a>
        </div>

        @livewire('pharma.drug-bid-award.review-workspace')
    </div>
@endsection
