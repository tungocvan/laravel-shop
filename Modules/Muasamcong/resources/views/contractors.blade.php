@extends('Admin::layouts.master')

@section('title', 'Lịch sử nhà thầu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Lịch sử nhà thầu</h1>
                <p class="mt-1 text-sm text-gray-500">Tra cứu các gói thầu doanh nghiệp đã tham gia, đối chiếu KQLCNT và dữ liệu HSMT đã đồng bộ.</p>
            </div>

            <a href="{{ route('muasamcong.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                ← Về tra cứu thuốc
            </a>
        </div>

        @livewire('muasamcong.contractor-history')
    </div>
@endsection
