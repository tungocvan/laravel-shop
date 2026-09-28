@extends('ClientPortal::layouts.application')

@section('title', $hospital->name)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commercial Workspace · bệnh viện')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <a href="{{ route('client.pharma.commercial') }}" class="inline-flex h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">← Công việc bệnh viện</a>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Bệnh viện được phân công</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $hospital->name }}</h1>
        @if($hospital->address)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $hospital->address }}</p>
        @endif
        <div class="mt-4 inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200">
            {{ number_format((int) $hospital->assigned_products_count, 0, ',', '.') }} sản phẩm phụ trách
        </div>
    </section>

    <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="font-black text-slate-900">Danh sách sản phẩm</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Phạm vi bệnh viện đã được xác thực theo phân công của User. Danh sách sản phẩm và dữ liệu thương mại sẽ được nối ở Batch 2.</p>
    </section>
</div>
@endsection
