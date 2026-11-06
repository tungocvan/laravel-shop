@extends('ClientPortal::layouts.application')
@section('title', 'Tra cứu thuốc trúng thầu')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('content')
<div class="min-w-0 space-y-5">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về Tra cứu</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">Dữ liệu công khai đấu thầu</p>
        <h1 class="mt-2 text-2xl font-black">Tra cứu thuốc trúng thầu</h1>
        <p class="mt-2 text-sm leading-6 text-slate-300">Tra cứu kết quả giá thuốc trúng thầu. Dữ liệu chỉ để tham khảo, không thay thế giá kê khai hoặc giá bán.</p>
    </section>
    <section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <form action="{{ route('client.pharma.award-prices') }}" method="GET" class="flex flex-col gap-3 sm:flex-row">
            <label for="award-keyword" class="sr-only">Tên thuốc, hoạt chất, mã TBMT hoặc đơn vị trúng thầu</label>
            <input id="award-keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Tên thuốc, hoạt chất, mã TBMT..." class="min-w-0 flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-base" required>
            <button type="submit" class="min-h-11 rounded-2xl bg-slate-900 px-6 py-3 font-bold text-white">Tra cứu</button>
        </form>
        @error('keyword')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    </section>
    @if($result !== null)
        @if(!($result['success'] ?? false))
            <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $result['message'] ?? 'Không thể tra cứu.' }}</div>
        @else
            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-6">
                    <h2 class="font-bold text-slate-900">Kết quả: {{ number_format($result['total'] ?? 0, 0, ',', '.') }} bản ghi</h2>
                    <p class="mt-1 text-xs text-slate-500">Hiển thị tối đa 20 kết quả đầu tiên từ nguồn công khai. Không tự động đồng bộ vào danh mục thuốc.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($result['items'] ?? [] as $item)
                        @php
                            $company = $item['winningName'] ?? [];
                            $company = is_array($company) ? implode(', ', array_filter($company, 'is_scalar')) : (is_scalar($company) ? (string) $company : '');
                            $price = $item['donGia'] ?? null;
                        @endphp
                        <article class="space-y-2 p-4 sm:p-6">
                            <h3 class="font-bold text-slate-950">{{ $item['tenThuoc'] ?? 'Chưa có tên thuốc' }}</h3>
                            <div class="grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                <p><strong>Hoạt chất:</strong> {{ $item['tenHoatChat'] ?? '—' }}</p>
                                <p><strong>ĐVT:</strong> {{ $item['donViTinh'] ?? '—' }}</p>
                                <p><strong>Quy cách:</strong> {{ $item['quyCachDongGoi'] ?? '—' }}</p>
                                <p><strong>Mã TBMT:</strong> {{ $item['maTbmt'] ?? '—' }}</p>
                                <p><strong>Đơn vị trúng thầu:</strong> {{ $company ?: '—' }}</p>
                                <p><strong>Đơn giá trúng thầu:</strong> {{ is_numeric($price) ? number_format((float) $price, 0, ',', '.').' đ' : '—' }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="p-6 text-sm text-slate-500">Không tìm thấy kết quả phù hợp.</p>
                    @endforelse
                </div>
            </section>
        @endif
    @endif
</div>
@endsection
