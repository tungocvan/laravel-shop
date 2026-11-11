@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? $feature['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-5xl space-y-5 px-3 py-4 sm:px-6" data-loan-capability="{{ $workspace }}">
    <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap items-center gap-3">
        <a href="{{ route('client.loans.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">← Tổng quan khoản vay</a>
    </nav>
    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $presentation['eyebrow'] ?? $feature['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $presentation['page_title'] ?? $feature['page_title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $presentation['page_description'] ?? $feature['page_description'] }}</p>
    </header>
    @if($workspace === 'contracts' && auth('web')->user()?->can('client.loans.contracts.create'))
    <div class="flex justify-end"><a href="{{ route('client.loans.contracts.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">+ Tạo hợp đồng vay</a></div>
    @endif
    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" aria-labelledby="loan-capability-next">
        <h2 id="loan-capability-next" class="text-lg font-semibold text-slate-900">Tiếp tục thao tác</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Không gian nghiệp vụ riêng đang được chuyển đổi theo từng giai đoạn. Các chức năng đã hoạt động vẫn có thể sử dụng từ màn hình hiện tại, không thay đổi dữ liệu hay quy trình xử lý.</p>
        <a href="{{ route('client.loans.dashboard') }}#{{ $legacyAnchor }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white hover:bg-blue-900">Mở chức năng hiện tại →</a>
    </section>
</main>
@endsection
