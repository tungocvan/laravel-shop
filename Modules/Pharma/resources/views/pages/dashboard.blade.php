@extends('Admin::layouts.master')

@section('title', 'Dashboard Pharma')

@section('content')
    @php
        $capabilities = $dashboard['capabilities'];
        $metrics = $dashboard['metrics'];
        $priceList = $dashboard['price_list'];
        $formatCount = static fn (array $metric): string => $metric['available'] ? number_format($metric['count']) : '—';
        $metricCards = [
            [
                'label' => 'Danh mục thuốc / HSSP',
                'value' => $formatCount($metrics['medicines']),
                'route' => route('admin.pharma.hssp.index'),
                'meta' => $metrics['medicines']['available'] ? 'Hồ sơ sản phẩm đang quản lý' : 'Dữ liệu chưa sẵn sàng',
            ],
            [
                'label' => 'Kết quả trúng thầu',
                'value' => $formatCount($metrics['drug_bid_awards']),
                'route' => route('admin.pharma.drug-bid-awards.index'),
                'meta' => $metrics['drug_bid_awards']['available'] ? 'Bản ghi kết quả lựa chọn nhà thầu' : 'Dữ liệu chưa sẵn sàng',
            ],
            [
                'label' => 'Theo dõi nhà cung cấp',
                'value' => $formatCount($metrics['supplier_trackings']),
                'route' => route('admin.pharma.supplier-trackings.index'),
                'meta' => $metrics['supplier_trackings']['available'] ? 'Bản ghi theo dõi đang quản lý' : 'Dữ liệu chưa sẵn sàng',
            ],
        ];
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard Pharma</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Điểm vào tập trung cho các workspace Pharma, giúp kiểm tra nhanh dữ liệu và mở đúng chức năng mà không cần nhớ URL.</p>
            </div>

            @if ($capabilities['create'])
                <a href="{{ route('admin.pharma.price-lists.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tạo bảng giá Excel</a>
            @endif
        </header>

        <section aria-labelledby="pharma-summary-heading">
            <div class="mb-4">
                <h2 id="pharma-summary-heading" class="text-lg font-semibold text-slate-900">Tổng quan dữ liệu</h2>
                <p class="mt-1 text-sm text-slate-500">Số liệu nhẹ phục vụ điều hướng và kiểm tra vận hành.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <a href="{{ $card['route'] }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-950">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </a>
                @endforeach

                <a href="{{ $capabilities['create'] ? route('admin.pharma.price-lists.create') : '#' }}" @if (! $capabilities['create']) aria-disabled="true" @endif class="rounded-2xl border p-5 shadow-sm {{ $priceList['ready'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }} {{ $capabilities['create'] ? 'transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500' : 'cursor-default' }}">
                    <p class="text-sm font-medium text-slate-600">Workbook bảng giá</p>
                    <p class="mt-2 text-lg font-bold {{ $priceList['ready'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $priceList['ready'] ? 'Sẵn sàng' : 'Thiếu file nguồn' }}</p>
                    <p class="mt-2 break-all text-xs leading-5 text-slate-500">{{ $priceList['source'] }}</p>
                </a>
            </div>
        </section>

        <section aria-labelledby="pharma-workspaces-heading">
            <div class="mb-4">
                <h2 id="pharma-workspaces-heading" class="text-lg font-semibold text-slate-900">Không gian quản lý</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Dashboard chỉ tổng hợp và điều hướng; thao tác nghiệp vụ vẫn thực hiện trong từng workspace chuyên trách.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <a href="{{ route('admin.pharma.hssp.index') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 transition hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Danh mục</p><h3 class="mt-2 text-lg font-bold text-slate-950">Thuốc / HSSP</h3><p class="mt-2 text-sm leading-6 text-slate-600">Tra cứu và quản lý hồ sơ sản phẩm Pharma.</p>
                </a>
                <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 transition hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Đấu thầu</p><h3 class="mt-2 text-lg font-bold text-slate-950">Kết quả trúng thầu</h3><p class="mt-2 text-sm leading-6 text-slate-600">Mở workspace kết quả lựa chọn nhà thầu.</p>
                </a>
                <a href="{{ route('admin.pharma.supplier-trackings.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 transition hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Nhà cung cấp</p><h3 class="mt-2 text-lg font-bold text-slate-950">Theo dõi nhà cung cấp</h3><p class="mt-2 text-sm leading-6 text-slate-600">Theo dõi giá, cam kết và dữ liệu nhà cung cấp.</p>
                </a>
                @if ($capabilities['official_facilities'])
                    <a href="{{ route('admin.pharma.official-facilities.index') }}" class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 transition hover:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Master Data</p><h3 class="mt-2 text-lg font-bold text-slate-950">Cơ sở KCB chính thức</h3><p class="mt-2 text-sm leading-6 text-slate-600">Staging, đối soát và import cơ sở y tế chính thức vào Partner.</p>
                    </a>
                @endif
                @if ($capabilities['create'])
                    <a href="{{ route('admin.pharma.price-lists.create') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 transition hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Excel</p><h3 class="mt-2 text-lg font-bold text-slate-950">Tạo bảng giá</h3><p class="mt-2 text-sm leading-6 text-slate-600">Chọn sản phẩm và cột để xuất bảng giá từ workbook nguồn.</p>
                    </a>
                @endif
            </div>
        </section>

        @if ($capabilities['create'])
            <section aria-labelledby="pharma-quick-actions-heading">
                <div class="mb-4"><h2 id="pharma-quick-actions-heading" class="text-lg font-semibold text-slate-900">Thao tác nhanh</h2><p class="mt-1 text-sm text-slate-500">Các hành động chỉ xuất hiện khi tài khoản có quyền tạo dữ liệu Pharma.</p></div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.pharma.hssp.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Thêm thuốc / HSSP</a>
                    <a href="{{ route('admin.pharma.drug-bid-awards.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Thêm kết quả trúng thầu</a>
                    <a href="{{ route('admin.pharma.supplier-trackings.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Thêm theo dõi NCC</a>
                    <a href="{{ route('admin.pharma.price-lists.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Tạo bảng giá</a>
                </div>
            </section>
        @endif
    </div>
@endsection
