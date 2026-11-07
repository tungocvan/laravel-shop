@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-5">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về Tra cứu</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-950">Dữ liệu GMP đã lưu</h2>
                <p class="mt-1 text-sm text-slate-600">Chỉ kết nối DAV khi bạn chủ động yêu cầu. Không chạy lịch tự động và không giữ các phiên bản Excel cũ.</p>
            </div>
            <form method="POST" action="{{ route('client.pharma.domestic-gmp.refresh') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-800 sm:w-auto">Kiểm tra và cập nhật từ DAV</button>
            </form>
        </div>
    </section>

    @if (session('gmp_success'))
        <div role="status" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">{{ session('gmp_success') }}</div>
    @endif
    @if (session('gmp_error') || $error)
        <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900">{{ session('gmp_error') ?: $error }}</div>
    @endif

    @if ($snapshot)
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-700">Phiên bản đang sử dụng</p>
            <h2 class="mt-2 text-lg font-black text-slate-950">{{ $snapshot['publication']['title'] }}</h2>
            <p class="mt-2 text-sm text-slate-600">Ngày đăng: {{ $snapshot['publication']['published'] ?: 'Không xác định' }}</p>
            <p class="mt-1 text-sm text-slate-600">Lần cập nhật thành công: {{ $snapshot['checked_at'] }}</p>
            <a href="{{ $snapshot['publication']['url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-indigo-700 underline">Xem bài gốc trên DAV ↗</a>
        </section>

        <section aria-label="Danh sách Excel GMP trong nước" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (['manufacturers' => 'Cơ sở sản xuất thuốc trong nước đạt GMP', 'packaging' => 'Cơ sở sản xuất bao bì dược phẩm đạt GMP', 'business_certificates' => 'Giấy chứng nhận đủ điều kiện kinh doanh dược'] as $key => $label)
                <article class="flex min-w-0 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">{{ $label }}</h3>
                    <p class="mt-3 break-words text-sm text-slate-600">{{ $snapshot['files'][$key]['name'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">SHA-256: {{ substr($snapshot['files'][$key]['sha256'], 0, 12) }}…</p>
                    <a href="{{ route('client.pharma.domestic-gmp.download', ['group' => $key]) }}" class="mt-auto inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-700 px-4 py-3 text-center text-sm font-bold text-white hover:bg-indigo-800">Tải Excel đã lưu</a>
                </article>
            @endforeach
        </section>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-700">Chưa có Excel GMP được lưu. Nhấn <strong>Kiểm tra và cập nhật từ DAV</strong> để tải bộ ba file đầu tiên.</div>
    @endif
    <p class="text-sm text-slate-500">Nguồn: Cục Quản lý Dược. File được lưu riêng tư trên server và chỉ thay thế khi cả ba Excel đều hợp lệ. Không tự động nhập dữ liệu vào danh mục thuốc.</p>
</div>
@endsection
