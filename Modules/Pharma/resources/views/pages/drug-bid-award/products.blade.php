@extends('Admin::layouts.master')

@section('admin_container', 'full')

@section('title', 'Sản phẩm trúng thầu')

@section('content')
    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Kết quả trúng thầu</a>
        </div>
        @livewire('pharma.drug-bid-award.product-workspace', ['awardId' => $id])
    </div>
@endsection
