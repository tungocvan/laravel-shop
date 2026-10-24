@extends('Admin::layouts.master')

@section('title', 'Quản lý thu chi')

@section('content')
<div class="w-full space-y-5 px-2 py-4 sm:px-4">
    <header class="flex flex-col gap-2 border-b border-slate-200 pb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Finance</p>
        <h1 class="text-2xl font-bold text-slate-950">Quản lý thu / chi</h1>
        <p class="text-sm text-slate-600">Chọn chứng từ Pharma đã ghi sổ, lập phiếu thu hoặc chi và theo dõi số dư.</p>
    </header>
    <div class="flex justify-end"><a href="{{ route('admin.finance.debts.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-500">Quản lý công nợ →</a></div>
    @livewire('finance.workspace')
</div>
@endsection
