@extends('Admin::layouts.master')
@section('title','Chi tiết hoa hồng')
@section('admin_container','full')
@section('content')
@php
 $issue=$detail['issue'];
 $customer=$issue?->recipientPartner?->name ?: $issue?->recipient_name ?: '—';
 $manager=$issue?->manager?->name ?: '—';
@endphp
<div class="mx-auto max-w-6xl space-y-4 px-2 xl:px-3">
 <a href="{{ route('admin.pharma.inventory.commissions.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm">← Trung tâm hoa hồng</a>
 <section class="rounded-3xl bg-slate-950 px-6 py-6 text-white shadow-sm">
  <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Chi tiết phiếu xuất</p><h1 class="mt-2 text-2xl font-black tracking-tight">{{ $issue?->number ?: 'Phiếu #'.$issue?->id }}</h1>
  <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
   <div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-bold uppercase text-slate-300">Ngày xuất</p><p class="mt-1 font-black">{{ $issue?->issue_date?->format('d/m/Y') ?: '—' }}</p></div>
   <div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-bold uppercase text-slate-300">Khách hàng</p><p class="mt-1 font-black">{{ $customer }}</p></div>
   <div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-bold uppercase text-slate-300">Người phụ trách</p><p class="mt-1 font-black">{{ $manager }}</p></div>
   <div class="rounded-2xl bg-white/10 p-4"><p class="text-xs font-bold uppercase text-slate-300">Tổng hoa hồng</p><p class="mt-1 text-xl font-black tabular-nums">{{ number_format($detail['commission'],0,',','.') }} đ</p></div>
  </div>
 </section>
 <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tổng giá trị</p><p class="mt-1 text-xl font-black tabular-nums text-slate-950">{{ number_format($detail['revenue'],0,',','.') }} đ</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600">{{ $detail['rows']->count() }} sản phẩm</span></div></section>
 <section class="space-y-3">
 @foreach($detail['rows'] as $row)
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
   <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $row->source_type==='bid' ? 'Trúng thầu' : 'Bảng giá' }}</p><h2 class="mt-1 font-black text-slate-950">{{ $row->medicine?->name ?: 'Sản phẩm #'.$row->medicine_id }}</h2><p class="mt-1 text-sm text-slate-500">{{ $row->medicine?->medicine_code ?: '—' }}</p></div><span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-black text-emerald-800">{{ $row->status==='unresolved' ? 'Chưa xác định' : 'Đã ghi nhận' }}</span></div>
   <dl class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-4">
    <div><dt class="text-xs font-bold text-slate-400">SL thực xuất</dt><dd class="mt-1 font-black tabular-nums">{{ number_format((float)$row->quantity,0,',','.') }}</dd></div>
    <div><dt class="text-xs font-bold text-slate-400">Doanh số</dt><dd class="mt-1 font-black tabular-nums">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</dd></div>
    <div><dt class="text-xs font-bold text-slate-400">Chính sách</dt><dd class="mt-1 font-black tabular-nums">{{ $row->commission_percentage!==null ? rtrim(rtrim(number_format((float)$row->commission_percentage,4,'.',''),'0'),'.').'%' : '—' }}</dd></div>
    <div><dt class="text-xs font-bold text-slate-400">Hoa hồng</dt><dd class="mt-1 font-black tabular-nums">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</dd></div>
   </dl>
   @if($row->resolution_note)<p class="mt-3 text-xs font-semibold text-slate-400">{{ $row->resolution_note }}</p>@endif
  </article>
 @endforeach
 </section>
</div>
@endsection
