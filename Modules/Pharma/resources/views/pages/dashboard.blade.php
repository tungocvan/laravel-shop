@extends('Admin::layouts.master')

@section('title', 'Dashboard Pharma')

@section('content')
    @php
        $capabilities = $dashboard['capabilities'];
        $metrics = $dashboard['metrics'];
        $priceList = $dashboard['price_list'];
        $formatCount = static fn (array $metric): string => $metric['available'] ? number_format($metric['count']) : '—';
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma</p><h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard Pharma</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Điểm vào tập trung cho Medicine Master, HSSP và các workspace Pharma chuyên trách.</p></div>
            @if($capabilities['create'])<a href="{{ route('admin.pharma.medicines.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Thêm thuốc vào Medicine Master</a>@endif
        </header>

        <section>
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">Tổng quan dữ liệu</h2><p class="mt-1 text-sm text-slate-500">Medicine Master là nguồn chuẩn; HSSP là lớp hồ sơ bổ sung.</p></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm transition hover:border-indigo-400"><p class="text-sm font-medium text-slate-600">Danh mục thuốc chuẩn</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['medicines']) }}</p><p class="mt-2 text-xs text-slate-500">Medicine Master dùng chung toàn ERP</p></a>
                <a href="{{ route('admin.pharma.hssp.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm transition hover:border-emerald-400"><p class="text-sm font-medium text-slate-600">HSSP thuốc</p><p class="mt-2 text-3xl font-bold text-slate-950">→</p><p class="mt-2 text-xs text-slate-500">Hồ sơ sản phẩm gắn với Medicine Master</p></a>
                <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm transition hover:border-sky-400"><p class="text-sm font-medium text-slate-600">Kết quả trúng thầu</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['drug_bid_awards']) }}</p><p class="mt-2 text-xs text-slate-500">Bản ghi kết quả lựa chọn nhà thầu</p></a>
                <a href="{{ $capabilities['create'] ? route('admin.pharma.price-lists.create') : '#' }}" class="rounded-2xl border p-5 shadow-sm {{ $priceList['ready'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }}"><p class="text-sm font-medium text-slate-600">Workbook bảng giá</p><p class="mt-2 text-lg font-bold">{{ $priceList['ready'] ? 'Sẵn sàng' : 'Thiếu file nguồn' }}</p><p class="mt-2 break-all text-xs text-slate-500">{{ $priceList['source'] }}</p></a>
            </div>
        </section>

        <section>
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">Không gian quản lý</h2><p class="mt-1 text-sm text-slate-500">Mỗi workspace có ownership rõ ràng, không tạo master dữ liệu trùng nhau.</p></div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 hover:border-indigo-400"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Canonical Master</p><h3 class="mt-2 text-lg font-bold text-slate-950">Danh mục thuốc chuẩn</h3><p class="mt-2 text-sm leading-6 text-slate-600">Quản lý Medicine, Variant, SKU, Package, Alias và trạng thái có/chưa có HSSP.</p></a>
                <a href="{{ route('admin.pharma.hssp.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 hover:border-emerald-400"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Hồ sơ</p><h3 class="mt-2 text-lg font-bold text-slate-950">HSSP thuốc</h3><p class="mt-2 text-sm leading-6 text-slate-600">Quản lý version, link tài liệu, hiệu lực, xác minh và lịch sử hồ sơ.</p></a>
                <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 hover:border-sky-400"><p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Đấu thầu</p><h3 class="mt-2 text-lg font-bold text-slate-950">Kết quả trúng thầu</h3><p class="mt-2 text-sm leading-6 text-slate-600">Dữ liệu nghiệp vụ tham chiếu Medicine Master.</p></a>
                @if($capabilities['edit'])<a href="{{ route('admin.pharma.drug-bid-awards.review') }}" class="rounded-2xl border border-cyan-200 bg-cyan-50/60 p-5 hover:border-cyan-400"><p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Bid Intelligence</p><h3 class="mt-2 text-lg font-bold text-slate-950">Rà soát liên kết trúng thầu</h3><p class="mt-2 text-sm leading-6 text-slate-600">Đối chiếu kết quả trúng thầu với Medicine, SKU và Package canonical; xử lý các bản ghi cần xác nhận.</p></a>@endif
                <a href="{{ route('admin.pharma.supplier-trackings.index') }}" class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 hover:border-violet-400"><p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Nhà cung cấp</p><h3 class="mt-2 text-lg font-bold text-slate-950">Theo dõi nhà cung cấp</h3><p class="mt-2 text-sm leading-6 text-slate-600">Theo dõi giá, cam kết và dữ liệu nhà cung cấp.</p></a>
                @if($capabilities['official_facilities'])<a href="{{ route('admin.pharma.official-facilities.index') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 hover:border-amber-400"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Master Data</p><h3 class="mt-2 text-lg font-bold text-slate-950">Cơ sở KCB chính thức</h3><p class="mt-2 text-sm leading-6 text-slate-600">Staging, đối soát và import cơ sở y tế vào Partner.</p></a>@endif
                @if($capabilities['create'])<a href="{{ route('admin.pharma.price-lists.create') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-slate-400"><p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Bảng giá</p><h3 class="mt-2 text-lg font-bold text-slate-950">Tạo bảng giá</h3><p class="mt-2 text-sm leading-6 text-slate-600">Khởi tạo bảng giá Pharma và quản lý dữ liệu giá bán.</p></a>@endif
            </div>
        </section>
    </div>
@endsection
