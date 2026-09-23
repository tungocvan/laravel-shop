@extends('Admin::layouts.master')
@section('title','Chi tiết phiếu nhập')
@section('content')
@php $total=$receipt->items->sum(fn($item)=>(float)$item->quantity*(float)$item->unit_price_ex_vat); @endphp
<div class="mx-auto max-w-6xl space-y-5">
    <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu nhập</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-bold text-slate-950">{{ $receipt->number }}</h1>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $receipt->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $receipt->status === 'posted' ? 'Đã ghi sổ' : 'Nháp' }}</span>
            </div>
        </div>
        @can('edit_pharma')<a href="{{ route('admin.pharma.inventory.receipts.edit',$receipt) }}" class="rounded-xl border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-700">{{ $receipt->status === 'draft' ? 'Sửa phiếu' : 'Cập nhật thông tin' }}</a>@endcan
    </header>
    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-4">
        <div><div class="text-xs font-semibold uppercase text-slate-500">Ngày nhập</div><div class="mt-1 font-semibold">{{ $receipt->receipt_date->format('d/m/Y') }}</div></div>
        <div><div class="text-xs font-semibold uppercase text-slate-500">Nhà cung cấp</div><div class="mt-1 font-semibold">{{ $receipt->supplier_name }}</div></div>
        <div><div class="text-xs font-semibold uppercase text-slate-500">Số hóa đơn</div><div class="mt-1">{{ $receipt->invoice_number ?: '—' }}</div></div>
        <div><div class="text-xs font-semibold uppercase text-slate-500">Ngày hóa đơn</div><div class="mt-1">{{ $receipt->invoice_date?->format('d/m/Y') ?: '—' }}</div></div>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="min-w-[850px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">HSD</th><th class="px-4 py-3 text-right">SL</th><th class="px-4 py-3 text-right">Giá nhập chưa VAT</th><th class="px-4 py-3 text-right">Thành tiền</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@foreach($receipt->items as $item)<tr>
                <td class="px-4 py-4"><div class="font-semibold">{{ $item->medicine->name }}</div><div class="font-mono text-xs text-indigo-700">{{ $item->medicine->medicine_code }}</div></td>
                <td class="px-4 py-4 font-mono">{{ $item->batch_number }}</td><td class="px-4 py-4">{{ $item->expiry_date->format('d/m/Y') }}</td>
                <td class="px-4 py-4 text-right">{{ number_format((float)$item->quantity,0,',','.') }}</td>
                <td class="px-4 py-4 text-right">{{ number_format((float)$item->unit_price_ex_vat,0,',','.') }} đ</td>
                <td class="px-4 py-4 text-right font-semibold">{{ number_format((float)$item->quantity*(float)$item->unit_price_ex_vat,0,',','.') }} đ</td>
            </tr>@endforeach</tbody>
            <tfoot class="bg-slate-50"><tr><td colspan="5" class="px-4 py-4 text-right font-semibold">Tổng giá trị</td><td class="px-4 py-4 text-right text-base font-bold">{{ number_format($total,0,',','.') }} đ</td></tr></tfoot>
        </table></div>
    </section>
</div>
@endsection
