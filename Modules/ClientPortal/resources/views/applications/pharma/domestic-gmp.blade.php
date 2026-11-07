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

    @if ($error)
        <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900">{{ $error }}</div>
    @elseif ($publication)
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-700">Bài công bố được phát hiện</p>
            <h2 class="mt-2 text-lg font-black text-slate-950">{{ $publication['title'] }}</h2>
            <p class="mt-2 text-sm text-slate-600">Ngày đăng: {{ $publication['published'] ?: 'Không xác định' }}</p>
            <a href="{{ $publication['url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-indigo-700 underline">Xem bài gốc trên DAV ↗</a>
        </section>

        <section aria-label="Danh sách Excel GMP trong nước" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (['manufacturers' => 'Cơ sở sản xuất thuốc trong nước đạt GMP', 'packaging' => 'Cơ sở sản xuất bao bì dược phẩm đạt GMP', 'business_certificates' => 'Giấy chứng nhận đủ điều kiện kinh doanh dược'] as $key => $label)
                <article class="flex min-w-0 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">{{ $label }}</h3>
                    @if (isset($publication['files'][$key]))
                        <p class="mt-3 break-words text-sm text-slate-600">{{ $publication['files'][$key]['name'] }}</p>
                        <a href="{{ route('client.pharma.domestic-gmp.download', ['group' => $key]) }}" class="mt-auto inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-700 px-4 py-3 text-center text-sm font-bold text-white hover:bg-indigo-800">Tải Excel mới nhất</a>
                    @else
                        <p class="mt-3 text-sm text-amber-800">Bài viết chưa có file Excel thuộc nhóm này.</p>
                    @endif
                </article>
            @endforeach
        </section>
        <p class="text-sm text-slate-500">Nguồn: Cục Quản lý Dược. Tệp được kiểm tra lại từ DAV khi tải và lưu trong private storage theo SHA-256. Không tự động nhập dữ liệu vào danh mục thuốc.</p>
    @endif
</div>
@endsection
