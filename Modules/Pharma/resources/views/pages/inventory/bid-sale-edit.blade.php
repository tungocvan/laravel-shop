@extends('Admin::layouts.master')
@section('title','Sửa & duyệt đơn hàng thầu')
@section('admin_container','full')
@section('content')
<div class="mx-auto w-full max-w-[1480px] space-y-5">
 <header><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="text-sm font-semibold text-indigo-700">← {{ $issue->number }}</a><div class="mt-2 flex flex-wrap items-center gap-2"><h1 class="text-2xl font-bold text-slate-950">Sửa & duyệt đơn hàng thầu</h1><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">NHÁP</span></div><p class="mt-1 text-sm text-slate-500">Người duyệt có thể giảm số lượng thực xuất theo khả năng cấp hàng và chia số lượng đó vào một hoặc nhiều lô. Chỉ “Duyệt & ghi sổ” mới trừ tồn kho.</p></header>
 @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
 <form method="POST" action="{{ route('admin.pharma.inventory.issues.bid-sales.post',$issue) }}" class="space-y-5">@csrf
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="grid gap-4 lg:grid-cols-4">
   <label class="text-sm font-semibold">Ngày xuất<input type="date" name="issue_date" value="{{ old('issue_date',$issue->issue_date->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
   <div><p class="text-sm font-semibold">Chủ đầu tư</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $issue->bid_investor_name ?: $issue->bid_investor_code }}</div></div>
   <div><p class="text-sm font-semibold">Khách hàng / Bệnh viện</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $issue->recipient_name }}</div></div>
   <div><p class="text-sm font-semibold">Người phụ trách</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $rows->pluck('manager_names')->filter()->unique()->implode(', ') ?: 'Chưa phân công' }}</div></div>
  </div></section>
  @foreach($rows as $row)
   @php($lots=$balances->get($issue->items->firstWhere('id',$row['item_id'])?->medicine_id,collect()))
   <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="grid gap-3 border-b border-slate-100 px-5 py-4 lg:grid-cols-[minmax(260px,1fr)_repeat(6,minmax(90px,auto))] lg:items-end">
     <div><div class="font-mono text-xs font-bold text-indigo-700">{{ $row['medicine_code'] }}</div><h2 class="font-bold text-slate-900">{{ $row['medicine_name'] }}</h2><div class="text-xs text-slate-500">{{ $row['unit'] ?: '—' }}</div></div>
     <div class="text-right text-xs text-slate-500">Phân bổ<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['allocated_quantity'],0,',','.') }}</div></div>
     <div class="text-right text-xs text-slate-500">Đã xuất<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['issued_quantity'],0,',','.') }}</div></div>
     <div class="text-right text-xs text-slate-500">Còn lại<div class="mt-1 text-sm font-bold text-emerald-700">{{ number_format($row['remaining_quantity'],0,',','.') }}</div></div>
     <div class="text-right text-xs text-slate-500">Tồn khả dụng<div class="mt-1 text-sm font-bold">{{ number_format($row['available_stock'],0,',','.') }}</div></div>
     <div class="text-right text-xs text-slate-500">Giá trúng thầu<div class="mt-1 text-sm font-bold">{{ number_format($row['winning_price'],0,',','.') }} đ</div></div>
     <label class="text-xs font-semibold text-slate-600">SL duyệt<input type="number" min="0.001" max="{{ $row['remaining_quantity'] }}" step="0.001" name="quantities[{{ $row['item_id'] }}]" value="{{ old('quantities.'.$row['item_id'],$row['quantity']) }}" class="mt-1 w-32 rounded-lg border border-slate-300 px-3 py-2 text-right font-bold"></label>
    </div>
    <div class="overflow-auto"><table class="min-w-[760px] w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="w-24 px-4 py-2 text-center">Ưu tiên</th><th class="px-4 py-2 text-left">Lô</th><th class="px-4 py-2 text-center">HSD</th><th class="px-4 py-2 text-right">Tồn lô</th><th class="w-48 px-4 py-2 text-right">SL lấy từ lô</th></tr></thead><tbody class="divide-y divide-slate-100">
     @forelse($lots as $lot)<tr><td class="px-4 py-3 text-center">@if($loop->first)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">FEFO</span>@else{{ $loop->iteration }}@endif</td><td class="px-4 py-3 font-semibold">{{ $lot->batch_number }}</td><td class="px-4 py-3 text-center">{{ $lot->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$lot->quantity_on_hand,0,',','.') }}</td><td class="px-4 py-3"><input type="hidden" name="batches[{{ $row['item_id'] }}][{{ $loop->index }}][balance_id]" value="{{ $lot->id }}"><input type="number" min="0" max="{{ (float)$lot->quantity_on_hand }}" step="0.001" name="batches[{{ $row['item_id'] }}][{{ $loop->index }}][quantity]" value="{{ old('batches.'.$row['item_id'].'.'.$loop->index.'.quantity',$loop->first ? min($row['quantity'],(float)$lot->quantity_on_hand) : 0) }}" class="ml-auto block w-40 rounded-lg border border-slate-300 px-3 py-2 text-right"></td></tr>
     @empty<tr><td colspan="5" class="px-4 py-7 text-center font-semibold text-rose-600">Chưa có lô tồn khả dụng. Hãy giảm số lượng duyệt hoặc bổ sung tồn kho trước khi duyệt.</td></tr>@endforelse
    </tbody></table></div>
   </section>
  @endforeach
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><label class="text-sm font-semibold">Ghi chú<textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('notes',$issue->notes) }}</textarea></label><div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-500">FEFO là gợi ý ưu tiên. Có thể chia một thuốc qua nhiều lô; tổng SL lô phải bằng <b>SL duyệt</b>.</p><div class="flex gap-2"><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Hủy</a><button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white">Duyệt & ghi sổ</button></div></div></section>
 </form>
</div>
@endsection
