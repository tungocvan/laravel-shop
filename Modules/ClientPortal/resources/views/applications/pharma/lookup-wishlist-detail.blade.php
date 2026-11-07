@extends('ClientPortal::layouts.application')
@section('title', $favorite->medicine_name)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php
    $item = $favorite->snapshot ?? [];
    $context = $favorite->search_context ?? [];
    $isRegistration = in_array($favorite->source_type, ['dav_registration', 'traditional_registration'], true);
@endphp
<div class="mx-auto max-w-5xl space-y-4">
    <section class="rounded-3xl bg-slate-950 p-5 text-white sm:p-7">
        <a href="{{ route('client.pharma.lookups.wishlist') }}" class="inline-flex min-h-11 items-center text-sm text-slate-200">← Danh sách quan tâm</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $sourceLabel }}</p>
        <h1 class="mt-2 break-words text-2xl font-black">{{ $favorite->medicine_name }}</h1>
        <p class="mt-2 text-sm text-slate-300">Số đăng ký: {{ $favorite->registration_number ?: 'Chưa công bố' }}</p>
    </section>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Thông tin lưu tại thời điểm quan tâm. Dữ liệu nguồn có thể thay đổi; không dùng bản lưu này để đồng bộ trực tiếp vào Danh mục thuốc.</div>
    <section class="rounded-3xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-black">Thông tin đã lưu</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach(($isRegistration ? [
                'Tên thuốc' => 'name', 'Số đăng ký' => 'registration', 'Hoạt chất / dược liệu' => 'ingredient',
                'Hàm lượng' => 'strength', 'Dạng bào chế' => 'dosage_form', 'Đóng gói' => 'packaging',
                'Ngày cấp' => 'issued_at', 'Ngày hết hạn' => 'expires_at', 'Số quyết định' => 'decision',
                'Nhà sản xuất' => 'manufacturer', 'Công ty đăng ký' => 'registrant',
            ] : [
                'Tên thuốc' => 'name', 'Số đăng ký' => 'registration', 'Thành phần' => 'ingredient',
                'Hàm lượng' => 'strength', 'Dạng bào chế' => 'dosage', 'Quy cách' => 'package',
                'Đơn vị tính' => 'unit', 'Nhà sản xuất' => 'manufacturer', 'Đơn vị kê khai' => 'declarant',
            ]) as $label => $key)
                <div class="min-w-0">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 whitespace-pre-line break-words text-sm text-slate-900">{{ ($item[$key] ?? '') !== '' ? $item[$key] : 'Chưa công bố' }}</dd>
                </div>
            @endforeach
        </dl>
        @unless($isRegistration)
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <p class="text-sm">Giá bán buôn: <strong>{{ is_numeric($item['wholesale'] ?? null) ? number_format($item['wholesale'], 0, ',', '.').' đ' : 'Chưa công bố' }}</strong></p>
                <p class="text-sm">Giá bán lẻ: <strong>{{ is_numeric($item['retail'] ?? null) ? number_format($item['retail'], 0, ',', '.').' đ' : 'Chưa công bố' }}</strong></p>
            </div>
        @endunless
    </section>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route($sourceRoute, ['q' => $context['q'] ?? '', 'field' => $context['field'] ?? 'name', 'page' => $context['page'] ?? 1]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-5 text-sm font-bold text-white">Tra cứu lại nguồn</a>
        <form method="POST" action="{{ route('client.pharma.lookups.wishlist.remove', ['sourceType' => $favorite->source_type, 'sourceId' => $favorite->source_id]) }}">
            @csrf
            @method('DELETE')
            <button class="min-h-11 rounded-xl border border-rose-300 px-5 text-sm font-bold text-rose-700">Bỏ quan tâm</button>
        </form>
    </div>
</div>
@endsection
