@extends('Admin::layouts.master')

@section('title', 'Phân bổ Drug Award')

@section('content')
    <div class="container-fluid">
        <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="mb-4 inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Kết quả trúng thầu</a>
        @livewire('pharma.drug-bid-award.allocation-workspace', ['awardId' => $id])
    </div>
@endsection
