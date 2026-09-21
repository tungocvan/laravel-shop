@extends('Admin::layouts.master')

@section('admin_container', 'full')

@section('title', 'Sản phẩm trúng thầu')

@section('content')
    <div class="container-fluid">
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Kết quả trúng thầu</a>
            @can('view_pharma_commercial_policies')<a href="{{ route('admin.pharma.drug-bid-awards.commercial-policy', $id) }}" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Chính sách kinh doanh</a>@endcan
        </div>
        @livewire('pharma.drug-bid-award.product-workspace', ['awardId' => $id])
    </div>
@endsection
