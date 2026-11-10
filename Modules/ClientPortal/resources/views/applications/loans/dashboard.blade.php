@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])

@section('content')
<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $overviewPresentation['eyebrow'] ?? 'Khoản vay' }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $overviewPresentation['page_title'] ?? 'Tổng quan khoản vay' }}</h1>
        <p class="mt-2 text-slate-600">{{ $overviewPresentation['page_description'] ?? 'Theo dõi nghĩa vụ thanh toán.' }}</p>
        <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
            Chức năng đang được thiết lập. Chưa có dữ liệu khoản vay nào được ghi nhận qua giao diện này.
        </div>
    </div>
</div>
@endsection
