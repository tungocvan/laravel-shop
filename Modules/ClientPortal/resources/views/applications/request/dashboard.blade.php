@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.request.dashboard'))
@section('mobile-nav')@include('ClientPortal::applications.request.partials.mobile-nav')@endsection

@section('content')
<section class="rounded-3xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-9">
    <p class="text-sm font-semibold text-slate-300">Không gian tác nghiệp cá nhân</p>
    <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">{{ $applicationPresentation['name'] ?? $application['name'] }}</h1>
    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">{{ $applicationPresentation['description'] ?? $application['description'] }}</p>
</section>

@php($featureRoutes = $features->filter(fn ($feature) => !empty($feature['route']) && Route::has($feature['route']))->keyBy('route'))
<section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Đề nghị của tôi</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $requestCounts['all'] ?? 0 }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ $requestCounts['draft'] ?? 0 }} nháp · {{ $requestCounts['processing'] ?? 0 }} đang xử lý</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cần bổ sung</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $requestCounts['returned'] ?? 0 }}</p>
        <p class="mt-2 text-sm text-slate-500">Đề nghị đã được trả lại cho bạn.</p>
    </div>
    @if($featureRoutes->has('client.request.inbox'))
        <a href="{{ route('client.request.inbox') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cần phê duyệt</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $approvalCounts['pending'] ?? 0 }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $approvalCounts['warning'] ?? 0 }} sắp hạn · {{ $approvalCounts['overdue'] ?? 0 }} quá hạn</p>
        </a>
    @else
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cần phê duyệt</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $approvalCounts['pending'] ?? 0 }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $approvalCounts['warning'] ?? 0 }} sắp hạn · {{ $approvalCounts['overdue'] ?? 0 }} quá hạn</p>
        </div>
    @endif
    @if($featureRoutes->has('client.request.processed'))
        <a href="{{ route('client.request.processed') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Đã xử lý</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $processedCounts['all'] ?? 0 }}</p>
            <p class="mt-2 text-sm text-slate-500">Lịch sử quyết định phê duyệt của bạn.</p>
        </a>
    @else
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Đã xử lý</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $processedCounts['all'] ?? 0 }}</p>
            <p class="mt-2 text-sm text-slate-500">Lịch sử quyết định phê duyệt của bạn.</p>
        </div>
    @endif
</section>

<section class="mt-7">
    <div class="mb-4">
        <h2 class="text-xl font-bold">Chức năng của bạn</h2>
        <p class="mt-1 text-sm text-slate-500">Các chức năng hiển thị theo quyền Web Guard đã được quản trị viên cấp.</p>
    </div>

    @if($features->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h3 class="font-semibold">Chưa có chức năng được cấp quyền</h3>
            <p class="mt-2 text-sm text-slate-500">Liên hệ quản trị viên để được cấp thêm quyền sử dụng.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($features as $feature)
                @php($routeExists = !empty($feature['route']) && Route::has($feature['route']))
                @if($routeExists)
                    <a href="{{ route($feature['route']) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Sẵn sàng</p>
                        <h3 class="mt-2 text-lg font-bold">{{ $feature['name'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                    </a>
                @else
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 opacity-70">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Sắp có</p>
                        <h3 class="mt-2 text-lg font-bold">{{ $feature['name'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</section>
@endsection
