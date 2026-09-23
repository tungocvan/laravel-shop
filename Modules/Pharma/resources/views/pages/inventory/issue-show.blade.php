@extends('Admin::layouts.master')
@section('title','Chi tiết phiếu xuất')
@section('content')
<div class="mx-auto max-w-6xl space-y-5">
<a href="{{ route('admin.pharma.inventory.issues.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu xuất</a>
<header><h1 class="text-2xl font-bold">{{ $issue->number }}</h1><p class="text-sm text-slate-500">{{ $issue->issue_date->format('d/m/Y') }} · {{ $issue->recipient_name ?: 'Chưa chọn nơi nhận' }} · {{ $issue->status==='posted'?'Đã ghi sổ':'Nháp' }}</p></header>
<section class="overflow-hidden rounded-2xl border bg-white"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="p-3">Thuốc</th><th class="p-3">Số lô</th><th class="p-3">Hạn dùng</th><th class="p-3 text-right">SL</th><th class="p-3 text-right">Giá vốn</th><th class="p-3 text-right">Thành tiền</th></tr></thead><tbody class="divide-y">@foreach($issue->items as $item)<tr><td class="p-3"><b>{{ $item->medicine->medicine_code }}</b><br>{{ $item->medicine->name }}</td><td class="p-3">{{ $item->batch_number }}</td><td class="p-3">{{ $item->expiry_date->format('d/m/Y') }}</td><td class="p-3 text-right">{{ number_format((float)$item->quantity,0,',','.') }}</td><td class="p-3 text-right">{{ number_format((float)$item->unit_price,0,',','.') }} đ</td><td class="p-3 text-right font-semibold">{{ number_format((float)$item->quantity*(float)$item->unit_price,0,',','.') }} đ</td></tr>@endforeach</tbody><tfoot class="bg-slate-50"><tr><td colspan="5" class="p-3 text-right font-bold">Tổng giá trị</td><td class="p-3 text-right font-bold">{{ number_format($issue->items->sum(fn($i)=>(float)$i->quantity*(float)$i->unit_price),0,',','.') }} đ</td></tr></tfoot></table></section>
</div>
@endsection
