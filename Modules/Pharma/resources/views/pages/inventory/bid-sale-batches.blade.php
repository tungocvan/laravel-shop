@extends('Admin::layouts.master')
@section('title','Chọn lô ghi sổ '.$issue->number)
@section('admin_container','full')
@section('content')
<div class="mx-auto w-full max-w-[1380px] space-y-5">
 <header><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="text-sm font-semibold text-indigo-700">← {{ $issue->number }}</a><h1 class="mt-2 text-2xl font-bold text-slate-950">Chọn lô & ghi sổ hàng thầu</h1><p class="mt-1 text-sm text-slate-500">Phiếu vẫn đang ở trạng thái nháp. Chọn lô thực xuất cho từng mặt hàng; tồn kho chỉ được trừ sau khi xác nhận ghi sổ.</p></header>
 @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
 <form method="POST" action="{{ route('admin.pharma.inventory.issues.bid-sales.post',$issue) }}" class="space-y-5">@csrf
  @foreach($issue->items as $item)
   @php($lots=$balances->get($item->medicine_id,collect()))
   <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"><div><div class="font-mono text-xs font-bold text-indigo-700">{{ $item->medicine?->medicine_code }}</div><h2 class="font-bold text-slate-900">{{ $item->medicine?->name }}</h2></div><div class="text-sm">Cần xuất: <b>{{ number_format((float)$item->quantity,0,',','.') }} {{ $item->medicine?->unit }}</b></div></div>
    <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[760px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">Ưu tiên</th><th class="px-3 py-2">Số lô</th><th class="px-3 py-2">HSD</th><th class="px-3 py-2 text-right">Tồn hiện tại</th><th class="px-3 py-2">SL lấy từ lô</th></tr></thead><tbody>
    @forelse($lots as $lot)<tr class="border-t border-slate-100"><td class="px-3 py-3">@if($loop->first)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">FEFO</span>@else{{ $loop->iteration }}@endif</td><td class="px-3 py-3 font-semibold">{{ $lot->batch_number }}</td><td class="px-3 py-3">{{ $lot->expiry_date->format('d/m/Y') }}</td><td class="px-3 py-3 text-right">{{ number_format((float)$lot->quantity_on_hand,0,',','.') }}</td><td class="px-3 py-3"><input type="hidden" name="batches[{{ $item->id }}][{{ $loop->index }}][balance_id]" value="{{ $lot->id }}"><input type="number" min="0" max="{{ (float)$lot->quantity_on_hand }}" step="0.001" name="batches[{{ $item->id }}][{{ $loop->index }}][quantity]" value="{{ $loop->first ? min((float)$item->quantity,(float)$lot->quantity_on_hand) : 0 }}" class="w-40 rounded-lg border border-slate-300 px-3 py-2"></td></tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center font-semibold text-rose-600">Không có lô tồn khả dụng cho thuốc này.</td></tr>@endforelse
    </tbody></table></div>
   </section>
  @endforeach
  <div class="flex justify-end gap-2"><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Để sau</a><button class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white">Xác nhận lô & ghi sổ</button></div>
 </form>
</div>
@endsection
