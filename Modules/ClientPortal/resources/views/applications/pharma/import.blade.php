@extends('ClientPortal::layouts.application')

@section('title', 'Import danh mục thuốc')
@section('app-name', 'Pharma')
@section('app-subtitle', 'Import danh mục thuốc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-mobile-navigation', true)
@section('hide-application-header', true)

@section('content')
<div class="mx-auto max-w-6xl space-y-5 pb-10">
    <nav class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('client.pharma.products') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700">← Danh mục thuốc</a>
        <a href="{{ route('client.pharma.dashboard') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700">Dashboard Pharma</a>
    </nav>
    <header>
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">PHARMA · MEDICINE MASTER</p>
        <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Import danh mục thuốc</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Tải Excel vào staging để chuẩn hóa, đối chiếu và phân loại. Chưa có dữ liệu nào được ghi vào Medicine Master khi tải lên.</p>
    </header>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <strong>Không thể tải tệp:</strong> {{ $errors->first() }}
        </div>
    @endif
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-slate-900">1. Tải nguồn danh mục</h2>
                <p class="mt-1 text-xs text-slate-500">Tệp XLSX/CSV tối đa 10MB. Mã thuốc và SKU tham khảo được dùng để đối chiếu, không ghi đè trực tiếp.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('client.pharma.products.export')
                    <a href="{{ route('client.pharma.products.export.roundtrip') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">↓ Xuất dữ liệu để nhập lại (.xlsx)</a>
                @endcan
                <a href="{{ route('client.pharma.products.import.template') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-800">↓ Tải template (.xlsx)</a>
            </div>
        </div>
        <form method="POST" action="{{ route('client.pharma.products.import') }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-end gap-3">
            @csrf
            <label class="min-w-[220px] flex-1 text-xs font-bold text-slate-700">Tệp Excel / CSV
                <input type="file" name="file" accept=".xlsx,.csv" required class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
            </label>
            <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-800">Staging & phân loại</button>
        </form>
        <p class="mt-3 text-xs text-amber-700">Chỉ tiếp nhận và phân loại. Chức năng phê duyệt/commit chưa được bật ở Client Portal.</p>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-extrabold text-slate-900">2. Lịch sử import gần đây</h2>
            <p class="mt-1 text-xs text-slate-500">Chỉ hiển thị các batch do tài khoản của anh/chị tải lên tại Client Portal. Chọn một batch để xem phân loại từng dòng.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($batches as $batch)
                <a href="{{ route('client.pharma.products.import.batch', $batch->id) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition hover:bg-indigo-50/40">
                    <div class="min-w-0">
                        <p class="break-words text-sm font-bold text-slate-900">#{{ $batch->id }} · {{ $batch->source_file ?: 'Excel import' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $batch->created_at?->format('d/m/Y H:i') }} · {{ $batch->status }} · {{ $batch->total_rows }} dòng</p>
                    </div>
                    <div class="flex flex-wrap gap-1 text-xs font-semibold">
                        <span class="rounded-lg bg-blue-50 px-2 py-1 text-blue-700">Mới {{ $batch->new_rows }}</span>
                        <span class="rounded-lg bg-emerald-50 px-2 py-1 text-emerald-700">Cập nhật {{ $batch->update_rows }}</span>
                        <span class="rounded-lg bg-amber-50 px-2 py-1 text-amber-800">Rà soát {{ $batch->review_rows }}</span>
                        <span class="rounded-lg bg-red-50 px-2 py-1 text-red-700">Xung đột {{ $batch->conflict_rows }}</span>
                    </div>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Chưa có lịch sử import tại Client Portal.</p>
            @endforelse
        </div>
        @if($batches->hasPages())<div class="border-t border-slate-100 px-5 py-3">{{ $batches->links() }}</div>@endif
    </section>
</div>
@endsection
