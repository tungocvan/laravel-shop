@extends('Admin::layouts.master')

@section('admin_container', 'full')
@section('title', 'Chính sách kinh doanh')

@section('content')
<div class="container-fluid">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.pharma.drug-bid-awards.allocations', $id) }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Sản phẩm / Phân bổ</a>
    </div>
    @livewire('pharma.drug-bid-award.commercial-policy-workspace', ['awardId' => $id])
</div>
@endsection
