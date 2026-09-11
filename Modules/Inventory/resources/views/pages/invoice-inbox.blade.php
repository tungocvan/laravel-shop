@extends('Admin::layouts.master')

@section('title', request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho')

@section('content')
<div class="space-y-6">
    @if(session('inventory_success'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('inventory_success') }}</div>
    @endif

    @if(session('inventory_error'))
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">{{ session('inventory_error') }}</div>
    @endif

    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">HÓA ĐƠN MUA HÀNG → KHO</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho' }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                @if(request()->filled('inbox'))
                    Đối chiếu mặt hàng, bổ sung số lô/hạn sử dụng, chọn kho và kiểm tra phiếu trước khi xác nhận nhập kho.
                @else
                    Chọn năm, tháng để xem hóa đơn mua hàng. Hóa đơn đủ dữ liệu có thể bắt đầu nhập kho ngay; hóa đơn thiếu chi tiết được đánh dấu để đồng bộ dữ liệu trước.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request()->filled('inbox'))
                @if($selectedInboxHeader?->source_invoice_id && $selectedInboxHeader?->receipt?->status !== 'CONFIRMED' && (bool) auth('admin')->user()?->can('inventory.receipt.manage'))
                    <form method="POST" action="{{ route('admin.inventory.invoice-inbox.refresh-source', ['inboxId' => $selectedInboxHeader->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">↻ Cập nhật lại từ hóa đơn nguồn</button>
                    </form>
                @endif
                <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">← Danh sách hóa đơn</a>
            @endif
            <a href="{{ route('admin.inventory.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300">Tổng quan kho</a>
        </div>
    </header>

    @if(request()->filled('inbox'))
        <livewire:inventory.invoice-inbox-workspace :selected-inbox-id="(int) request('inbox')" />
    @else
        <livewire:inventory.invoice-receiving-source-queue />
    @endif
</div>
@endsection
