@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-5">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">← Quay về Pharma</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <section aria-label="Nguồn tra cứu" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('client.pharma.dav-prices') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 active:scale-[.99] motion-reduce:transform-none">
            <div class="flex items-center justify-between gap-3">
                <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-700">💊</span>
                <span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span>
            </div>
            <div class="mt-5">
                <h2 class="font-bold text-slate-950">Tra cứu giá thuốc tân dược</h2>
                <p class="mt-1 text-sm text-slate-600">Tra cứu giá kê khai công bố từ DAV và đồng bộ thuốc vào danh mục.</p>
            </div>
        </a>

        <a href="{{ route('client.pharma.traditional-prices') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <div class="flex items-center justify-between gap-3"><span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">🌿</span><span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span></div>
            <div class="mt-5"><h2 class="font-bold text-slate-950">Tra cứu giá thuốc đông dược</h2><p class="mt-1 text-sm text-slate-600">Giá kê khai công bố từ Cổng Y Dược cổ truyền Bộ Y tế.</p></div>
        </a>

        <a href="{{ route('client.pharma.award-prices') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <div class="flex items-center justify-between gap-3"><span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-2xl">📋</span><span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span></div>
            <div class="mt-5"><h2 class="font-bold text-slate-950">Tra cứu thuốc trúng thầu</h2><p class="mt-1 text-sm text-slate-600">Tra cứu độc lập giá trúng thầu từ nguồn công khai ngay trong Pharma.</p></div>
        </a>

        <a href="{{ route('client.pharma.drug-registrations') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <div class="flex items-center justify-between gap-3"><span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-2xl">📄</span><span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span></div>
            <div class="mt-5"><h2 class="font-bold text-slate-950">Công bố giấy đăng ký thuốc tân dược</h2><p class="mt-1 text-sm text-slate-600">Tra cứu giấy đăng ký lưu hành và hồ sơ công bố công khai từ DAV.</p></div>
        </a>

        <a href="{{ route('client.pharma.gmp') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <div class="flex items-center justify-between gap-3"><span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-white text-2xl">🛡️</span><span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span></div>
            <div class="mt-5"><h2 class="font-bold text-slate-950">Tra cứu GMP</h2><p class="mt-1 text-sm text-slate-600">Cơ sở sản xuất nước ngoài đáp ứng GMP, giấy chứng nhận và phạm vi được công bố.</p></div>
        </a>
        <a href="{{ route('client.pharma.domestic-gmp') }}" class="group flex min-h-44 flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <div class="flex items-center justify-between gap-3"><span aria-hidden="true" class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-2xl">🏭</span><span aria-hidden="true" class="text-lg font-bold text-indigo-600">↗</span></div>
            <div class="mt-5"><h2 class="font-bold text-slate-950">GMP cơ sở sản xuất trong nước</h2><p class="mt-1 text-sm text-slate-600">Kiểm tra bài công bố mới và tải ba nhóm Excel từ DAV.</p></div>
        </a>
    </section>
</div>
@endsection
