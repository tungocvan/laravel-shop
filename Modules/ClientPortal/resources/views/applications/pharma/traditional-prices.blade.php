@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-4">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về Tra cứu</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <form method="GET" action="{{ route('client.pharma.traditional-prices') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label class="min-w-0 flex-1 text-sm font-semibold text-slate-700">
                Tên thuốc, thành phần hoặc số đăng ký
                <input type="search" name="q" value="{{ $keyword }}" maxlength="120" placeholder="Nhập từ khóa tra cứu" class="mt-2 min-h-12 w-full rounded-xl border border-slate-300 px-4 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>
            <x-native-touch type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-700 px-6 text-sm font-bold text-white">Tra cứu Bộ Y tế</x-native-touch>
        </form>
        <p class="mt-3 text-xs text-slate-500">Nguồn: Cổng Y Dược cổ truyền Bộ Y tế. Giá công bố không phải giá bán nội bộ hoặc giá trúng thầu. Thông tin có thể thay đổi tại nguồn.</p>
    </section>

    @if($error)
        <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $error }}</div>
    @elseif($keyword === '')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Nhập từ khóa để bắt đầu tra cứu.</div>
    @else
        <section class="space-y-3" aria-label="Kết quả tra cứu thuốc đông dược">
            <h2 class="text-lg font-black text-slate-900">{{ number_format($result['total'], 0, ',', '.') }} kết quả</h2>
            @forelse($result['items'] as $item)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3"><h3 class="text-base font-black text-slate-950">{{ $item['name'] }}</h3>
                    @if($item['match']['status'] === 'existing')
                        <a class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800" href="{{ route('client.pharma.products.edit', $item['match']['medicine_id']) }}">✓ Đã có trong danh mục</a>
                    @elseif($item['multi_package'])
                        <span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Nhiều quy cách — cần đối chiếu</span>
                    @elseif($item['match']['status'] === 'new' && $item['source_id'] !== '')
                        <a class="rounded-xl bg-indigo-700 px-3 py-2 text-sm font-semibold text-white" href="{{ route('client.pharma.traditional-prices.import.preview', ['q' => $keyword, 'page' => $page, 'source_id' => $item['source_id']]) }}">↓ Xem trước đồng bộ</a>
                    @else
                        <span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Cần đối chiếu thủ công</span>
                    @endif</div>
                    <p class="mt-1 text-sm text-slate-600">{{ $item['ingredient'] }}</p>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div><dt class="text-xs text-slate-500">Số đăng ký</dt><dd class="font-semibold">{{ $item['registration'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Giá kê khai (bán buôn)</dt><dd class="font-bold text-indigo-800">{{ $item['wholesale'] === null ? 'Chưa công bố' : number_format($item['wholesale'], 0, ',', '.').' đ / '.$item['unit'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Giá bán lẻ công bố</dt><dd>{{ $item['retail'] === null ? 'Chưa công bố' : number_format($item['retail'], 0, ',', '.').' đ / '.$item['unit'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Đơn vị kê khai</dt><dd>{{ $item['declarant'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nhà sản xuất</dt><dd>{{ $item['manufacturer'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Dạng bào chế</dt><dd>{{ $item['dosage'] ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nước sản xuất</dt><dd>{{ $item['country'] ?: '—' }}</dd></div>
                    </dl>
                    <details class="mt-3 border-t border-slate-100 pt-3 text-sm">
                        <summary class="min-h-10 cursor-pointer font-semibold text-indigo-700">Thông tin thêm</summary>
                        <p class="mt-2 text-slate-700">Quy cách: {{ $item['package'] ?: '—' }}</p>
                        <p class="mt-1 text-slate-700">Trạng thái: {{ $item['status'] ?: '—' }}</p>
                    </details>
                </article>
            @empty
                <div class="rounded-2xl bg-white p-5 text-sm text-slate-600">Không tìm thấy kết quả phù hợp.</div>
            @endforelse

            @if($result['total'] > 15)
                <nav aria-label="Phân trang đông dược" class="flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-600">Trang {{ $page }} / {{ (int) ceil($result['total'] / 15) }}</span>
                    <div class="flex gap-2">
                        @if($page > 1)
                            <a class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold" href="{{ route('client.pharma.traditional-prices', ['q' => $keyword, 'page' => $page - 1]) }}">Trước</a>
                        @endif
                        @if($page < min(1000, (int) ceil($result['total'] / 15)))
                            <a class="rounded-xl bg-indigo-700 px-4 py-3 text-sm font-semibold text-white" href="{{ route('client.pharma.traditional-prices', ['q' => $keyword, 'page' => $page + 1]) }}">Tiếp</a>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    @endif
</div>
@endsection
