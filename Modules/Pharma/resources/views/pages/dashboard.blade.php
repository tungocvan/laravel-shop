@extends('Admin::layouts.master')

@section('title', 'Dashboard Pharma')

@section('content')
    @php
        $capabilities = $dashboard['capabilities'];
        $metrics = $dashboard['metrics'];
        $priceLists = $dashboard['price_lists'];
        $formatCount = static fn (array $metric): string => $metric['available'] ? number_format($metric['count']) : '—';
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard Pharma</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Điểm vào tập trung cho Medicine Master, Bảng giá, Bid Intelligence và các workspace Pharma chuyên trách.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.price-lists.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100">Quản lý bảng giá</a>
                @if($capabilities['create'])<a href="{{ route('admin.pharma.price-lists.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">+ Tạo bảng giá</a>@endif
            </div>
        </header>

        <section>
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">Tổng quan dữ liệu</h2><p class="mt-1 text-sm text-slate-500">Medicine Master là nguồn thuốc chuẩn; Bảng giá v2 sử dụng dữ liệu database và bằng chứng trúng thầu đã liên kết.</p></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm transition hover:border-indigo-400"><p class="text-sm font-medium text-slate-600">Danh mục thuốc chuẩn</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['medicines']) }}</p><p class="mt-2 text-xs text-slate-500">Medicine Master dùng chung toàn ERP</p></a>
                <a href="{{ route('admin.pharma.price-lists.index') }}" class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 shadow-sm transition hover:border-violet-400"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-medium text-slate-600">Bảng giá</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['price_lists']) }}</p></div><span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-bold uppercase text-violet-700 shadow-sm">Price List v2</span></div><p class="mt-2 text-xs text-slate-500">{{ $priceLists['available'] ? number_format($priceLists['active']).' ACTIVE · '.number_format($priceLists['draft']).' DRAFT' : 'Dữ liệu chưa sẵn sàng' }}</p></a>
                <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm transition hover:border-sky-400"><p class="text-sm font-medium text-slate-600">Kết quả trúng thầu</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['drug_bid_awards']) }}</p><p class="mt-2 text-xs text-slate-500">Bằng chứng tham chiếu cho quyết định giá</p></a>
                <a href="{{ route('admin.pharma.supplier-trackings.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm transition hover:border-emerald-400"><p class="text-sm font-medium text-slate-600">Theo dõi nhà cung cấp</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $formatCount($metrics['supplier_trackings']) }}</p><p class="mt-2 text-xs text-slate-500">Theo dõi giá, cam kết và nguồn cung</p></a>
            </div>
        </section>

        <section class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-indigo-50 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[.14em] text-violet-700">Commercial Pricing</p><h2 class="mt-2 text-xl font-bold text-slate-950">Trung tâm Bảng giá Pharma</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Quản lý bảng giá chung và bảng giá khách hàng, giá kê khai, giá bán công ty, bằng chứng trúng thầu và cấu hình Excel chuyên nghiệp tại một workspace.</p></div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[430px]">
                    @foreach([['ACTIVE',$priceLists['active'],'emerald'],['DRAFT',$priceLists['draft'],'amber'],['Khách hàng',$priceLists['customer'],'sky'],['Bảng giá chung',$priceLists['global'],'indigo']] as [$label,$value,$tone])
                        <div class="rounded-2xl border border-slate-200 bg-white p-3 text-center shadow-sm"><p class="text-xl font-extrabold text-slate-950">{{ $priceLists['available'] ? number_format($value) : '—' }}</p><p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p></div>
                    @endforeach
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2 border-t border-violet-100 pt-5"><a href="{{ route('admin.pharma.price-lists.index') }}" class="inline-flex min-h-10 items-center rounded-xl bg-violet-600 px-4 text-sm font-bold text-white hover:bg-violet-700">Mở danh sách bảng giá</a>@if($capabilities['create'])<a href="{{ route('admin.pharma.price-lists.create') }}" class="inline-flex min-h-10 items-center rounded-xl border border-violet-200 bg-white px-4 text-sm font-bold text-violet-700 hover:bg-violet-50">Tạo bảng giá mới</a>@endif</div>
        </section>

        <section>
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">Không gian quản lý</h2><p class="mt-1 text-sm text-slate-500">Mỗi workspace có ownership rõ ràng, không tạo master dữ liệu trùng nhau.</p></div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 hover:border-indigo-400"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Canonical Master</p><h3 class="mt-2 text-lg font-bold text-slate-950">Danh mục thuốc chuẩn</h3><p class="mt-2 text-sm leading-6 text-slate-600">Quản lý Medicine, Variant, SKU, Package, Alias và trạng thái có/chưa có HSSP.</p></a>
                <a href="{{ route('admin.pharma.price-lists.index') }}" class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 hover:border-violet-400"><p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Bảng giá v2</p><h3 class="mt-2 text-lg font-bold text-slate-950">Quản lý bảng giá</h3><p class="mt-2 text-sm leading-6 text-slate-600">Danh sách, tạo/chỉnh sửa, kích hoạt, clone, xuất Excel và quản lý bảng giá khách hàng.</p></a>
                <a href="{{ route('admin.pharma.hssp.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 hover:border-emerald-400"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Hồ sơ</p><h3 class="mt-2 text-lg font-bold text-slate-950">HSSP thuốc</h3><p class="mt-2 text-sm leading-6 text-slate-600">Quản lý version, link tài liệu, hiệu lực, xác minh và lịch sử hồ sơ.</p></a>
                <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 hover:border-sky-400"><p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Đấu thầu</p><h3 class="mt-2 text-lg font-bold text-slate-950">Kết quả trúng thầu</h3><p class="mt-2 text-sm leading-6 text-slate-600">Dữ liệu nghiệp vụ tham chiếu Medicine Master và Bảng giá.</p></a>
                <a href="{{ route('admin.pharma.supplier-trackings.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 hover:border-emerald-400"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Supplier Commercial</p><h3 class="mt-2 text-lg font-bold text-slate-950">Theo dõi nhà cung cấp</h3><p class="mt-2 text-sm leading-6 text-slate-600">Quản lý sản phẩm, nhà cung cấp, giá vốn, hiệu lực, cam kết và hồ sơ thương mại.</p></a>
                @if($capabilities['edit'])<a href="{{ route('admin.pharma.drug-bid-awards.review') }}" class="rounded-2xl border border-cyan-200 bg-cyan-50/60 p-5 hover:border-cyan-400"><p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Bid Intelligence</p><h3 class="mt-2 text-lg font-bold text-slate-950">Rà soát liên kết trúng thầu</h3><p class="mt-2 text-sm leading-6 text-slate-600">Đối chiếu kết quả trúng thầu với Medicine, SKU và Package canonical.</p></a>@endif
                @if($capabilities['official_facilities'])<a href="{{ route('admin.pharma.official-facilities.source.index') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 hover:border-amber-400"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Master Data</p><h3 class="mt-2 text-lg font-bold text-slate-950">Cơ sở KCB chính thức</h3><p class="mt-2 text-sm leading-6 text-slate-600">Kho dữ liệu nguồn chính thức của Pharma: tra cứu BHXH, đồng bộ snapshot, rà soát rồi mới import/đối soát vào Partner.</p></a>@endif
            </div>
        </section>
    </div>
@endsection
