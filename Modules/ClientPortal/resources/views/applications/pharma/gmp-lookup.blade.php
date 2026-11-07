@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-4">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về Tra cứu</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <form method="GET" action="{{ route('client.pharma.gmp') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                Tên cơ sở, mã cơ sở hoặc giấy chứng nhận
                <input type="search" name="q" value="{{ $keyword }}" maxlength="200" placeholder="Ví dụ: Kern Pharma" class="mt-2 min-h-12 w-full rounded-xl border border-slate-300 px-4 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>
            <x-native-touch type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-700 px-6 text-sm font-bold text-white">Tra cứu GMP</x-native-touch>
        </form>
        <p class="mt-3 text-xs text-slate-500">Nguồn: Cục Quản lý Dược – Bộ Y tế. Dữ liệu được lưu tạm 5 phút; không tự cập nhật hồ sơ thuốc. Để trống từ khóa để xem danh sách công khai.</p>
    </section>

    @if($error)
        <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $error }}</div>
    @else
        <section aria-label="Kết quả tra cứu GMP" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-black text-slate-950">{{ number_format($result['total'], 0, ',', '.') }} cơ sở GMP</h2>
                <span class="text-xs text-slate-500">Trang {{ $page }} · {{ \Modules\Pharma\Services\DavForeignGmpLookup::PER_PAGE }} bản ghi/trang</span>
            </div>
            @forelse($result['items'] as $item)
                @php
                    $expiry = substr($item['expires_at'], 0, 10);
                    $expired = preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $expiry) && $expiry < now()->toDateString();
                    $dateText = static fn (string $date): string => preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})/', $date, $m) ? $m[3].'/'.$m[2].'/'.$m[1] : '—';
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-black text-slate-950">{{ $item['name'] ?: 'Cơ sở chưa có tên' }}</h3>
                            <p class="mt-1 break-words text-sm text-slate-600">{{ $item['address'] ?: 'Chưa có địa chỉ' }}</p>
                        </div>
                        <span class="rounded-xl px-3 py-2 text-xs font-bold {{ $expired ? 'bg-rose-50 text-rose-800' : ($item['active'] ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800') }}">{{ $expired ? 'Đã hết hạn' : ($item['active'] ? 'Đang công bố' : 'Chưa xác nhận hoạt động') }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Mã cơ sở</dt><dd class="font-semibold">{{ $item['company_code'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nguyên tắc GMP</dt><dd class="font-semibold">{{ $item['standard'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Giấy chứng nhận</dt><dd class="break-all font-semibold">{{ $item['certificate'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Ngày hết hạn</dt><dd class="font-semibold">{{ $dateText($item['expires_at']) }}</dd></div>
                    </dl>
                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-3">
                        @if($item['source_id'] !== '' && ctype_digit($item['source_id']))
                            <x-native-touch :href="route('client.pharma.gmp.show', ['sourceId' => $item['source_id'], 'q' => $keyword, 'page' => $page])" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 px-4 text-sm font-bold text-indigo-700">Xem chi tiết</x-native-touch>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Không tìm thấy cơ sở GMP phù hợp.</div>
            @endforelse

            @if($result['total'] > \Modules\Pharma\Services\DavForeignGmpLookup::PER_PAGE)
                <nav aria-label="Phân trang GMP" class="flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-slate-600">Trang {{ $page }} / {{ (int) ceil($result['total'] / \Modules\Pharma\Services\DavForeignGmpLookup::PER_PAGE) }}</span>
                    <div class="flex gap-2">
                        @if($page > 1)
                            <x-native-touch :href="route('client.pharma.gmp', ['q' => $keyword, 'page' => $page - 1])" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold">Trước</x-native-touch>
                        @endif
                        @if($page < min(1000, (int) ceil($result['total'] / \Modules\Pharma\Services\DavForeignGmpLookup::PER_PAGE)))
                            <x-native-touch :href="route('client.pharma.gmp', ['q' => $keyword, 'page' => $page + 1])" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 text-sm font-semibold text-white">Tiếp</x-native-touch>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    @endif
</div>
@endsection
