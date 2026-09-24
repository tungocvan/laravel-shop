@extends('Admin::layouts.master')
@section('title','Sửa & duyệt đơn hàng thầu')
@section('admin_container','full')
@section('content')
@php
$hasPostableStock=$rows->contains(fn($row)=>$balances->get($issue->items->firstWhere('id',$row['item_id'])?->medicine_id,collect())->isNotEmpty());
$hasUnresolvedShortage=$rows->contains(fn($row)=>$balances->get($issue->items->firstWhere('id',$row['item_id'])?->medicine_id,collect())->isEmpty());
$stockReady=$hasPostableStock && !$hasUnresolvedShortage;
@endphp
<div class="mx-auto w-full max-w-[1480px] space-y-5">
 <header><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="text-sm font-semibold text-indigo-700">← {{ $issue->number }}</a><div class="mt-2 flex flex-wrap items-center gap-2"><h1 class="text-2xl font-bold text-slate-950">Sửa & duyệt đơn hàng thầu</h1><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">NHÁP</span></div><p class="mt-1 text-sm text-slate-500">Người duyệt có thể giảm số lượng thực xuất theo khả năng cấp hàng và chia số lượng đó vào một hoặc nhiều lô. Chỉ “Duyệt & ghi sổ” mới trừ tồn kho.</p></header>
 @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
 <form id="bid-draft-form" method="POST" action="{{ route('admin.pharma.inventory.issues.bid-sales.update',$issue) }}" class="space-y-5">@csrf @method('PUT')
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="grid gap-4 lg:grid-cols-4">
   <label class="text-sm font-semibold">Ngày xuất<input type="date" name="issue_date" value="{{ old('issue_date',$issue->issue_date->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
   <div><p class="text-sm font-semibold">Chủ đầu tư</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $issue->bid_investor_name ?: $issue->bid_investor_code }}</div></div>
   <div><p class="text-sm font-semibold">Khách hàng / Bệnh viện</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $issue->recipient_name }}</div></div>
   <div><p class="text-sm font-semibold">Người phụ trách</p><div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold">{{ $rows->pluck('manager_names')->filter()->unique()->implode(', ') ?: 'Chưa phân công' }}</div></div>
  </div></section>
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
   <div class="flex flex-wrap items-start justify-between gap-3"><div><div class="flex items-center gap-2"><h2 class="font-bold text-slate-900">Sản phẩm trong phiếu</h2><span id="bid-product-count" data-base-count="{{ $rows->count() }}" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $rows->count() }} sản phẩm</span></div><p class="mt-1 text-xs text-slate-500">Bổ sung sản phẩm còn phân bổ của đúng Chủ đầu tư và Bệnh viện vào cùng phiếu nháp.</p></div>
   @if($addableAllocations->isNotEmpty())<button type="button" id="toggle-bid-add" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-100">+ Thêm sản phẩm trúng thầu</button>@endif</div>
   @if($addableAllocations->isNotEmpty())
   <div id="bid-add-panel" class="mt-4 hidden rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px_auto] lg:items-end">
     <div><label class="text-xs font-bold uppercase tracking-wide text-slate-500">Tìm sản phẩm trúng thầu</label>
      <select id="bid-add-allocation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">
       <option value="">Chọn mã / tên thuốc...</option>
       @foreach($addableAllocations as $candidate)<option value="{{ $candidate['id'] }}" data-max="{{ $candidate['remaining_quantity'] }}">{{ $candidate['medicine_code'] }} · {{ $candidate['medicine_name'] }} · Còn {{ number_format($candidate['remaining_quantity'],0,',','.') }} {{ $candidate['unit'] }} · {{ number_format($candidate['winning_price'],0,',','.') }} đ{{ $candidate['effective_until'] ? ' · HĐ đến '.$candidate['effective_until'] : '' }}</option>@endforeach
      </select>
     </div>
     <label class="text-xs font-bold uppercase tracking-wide text-slate-500">SL thêm<input id="bid-add-quantity" type="number" min="0.001" step="0.001" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-right text-sm font-bold" placeholder="0"></label>
     <button type="button" id="bid-add-button" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Thêm vào phiếu</button>
    </div>
    <p class="mt-3 text-xs text-slate-500">Sản phẩm sau khi thêm sẽ xuất hiện ngay trong danh sách bên dưới. Chưa ghi vào dữ liệu cho đến khi bấm <b>Lưu phiếu nháp</b>.</p>
   </div>
   @else<p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Không còn sản phẩm trúng thầu hợp lệ để bổ sung cho Chủ đầu tư/Bệnh viện này.</p>@endif
  </section>
  <div id="bid-pending-products" class="space-y-5"></div>
  @foreach($rows as $row)
   @php($lots=$balances->get($issue->items->firstWhere('id',$row['item_id'])?->medicine_id,collect()))
   <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
     <div class="flex flex-wrap items-start justify-between gap-3">
      <div><div class="font-mono text-xs font-bold text-indigo-700">{{ $row['medicine_code'] }}</div><h2 class="font-bold text-slate-900">{{ $row['medicine_name'] }}</h2><div class="text-xs text-slate-500">{{ $row['unit'] ?: '—' }}</div></div>
      <button type="submit" name="remove_items[]" value="{{ $row['item_id'] }}" form="bid-draft-form" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">Xóa khỏi đơn</button>
     </div>
     <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
      <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Phân bổ<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['allocated_quantity'],0,',','.') }}</div></div>
      <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Đã xuất<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['issued_quantity'],0,',','.') }}</div></div>
      <div class="rounded-xl bg-emerald-50 p-3 text-xs text-emerald-700">Còn lại<div class="mt-1 text-sm font-bold">{{ number_format($row['remaining_quantity'],0,',','.') }}</div></div>
      <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Tồn khả dụng<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['available_stock'],0,',','.') }}</div></div>
      <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Đơn giá trúng thầu<div class="mt-1 text-sm font-bold text-slate-900">{{ number_format($row['winning_price'],0,',','.') }} đ</div></div>
      <label class="rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-xs font-bold text-indigo-800">SL duyệt<input type="number" min="0.001" max="{{ $row['remaining_quantity'] }}" step="0.001" name="quantities[{{ $row['item_id'] }}]" value="{{ old('quantities.'.$row['item_id'],$row['quantity']) }}" class="mt-2 w-full rounded-lg border border-indigo-300 bg-white px-3 py-2 text-right text-sm font-bold text-slate-900"></label>
     </div>    </div>
    @if($canApprove)<div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Phê duyệt xuất kho · Chọn lô thực tế</p></div><div class="overflow-auto"><table class="min-w-[760px] w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="w-24 px-4 py-2 text-center">Ưu tiên</th><th class="px-4 py-2 text-left">Lô</th><th class="px-4 py-2 text-center">HSD</th><th class="px-4 py-2 text-right">Tồn lô</th><th class="w-48 px-4 py-2 text-right">SL lấy từ lô</th></tr></thead><tbody class="divide-y divide-slate-100">
     @forelse($lots as $lot)<tr><td class="px-4 py-3 text-center">@if($loop->first)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">FEFO</span>@else{{ $loop->iteration }}@endif</td><td class="px-4 py-3 font-semibold">{{ $lot->batch_number }}</td><td class="px-4 py-3 text-center">{{ $lot->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$lot->quantity_on_hand,0,',','.') }}</td><td class="px-4 py-3"><input type="hidden" name="batches[{{ $row['item_id'] }}][{{ $loop->index }}][balance_id]" value="{{ $lot->id }}"><input type="number" min="0" max="{{ (float)$lot->quantity_on_hand }}" step="0.001" name="batches[{{ $row['item_id'] }}][{{ $loop->index }}][quantity]" value="{{ old('batches.'.$row['item_id'].'.'.$loop->index.'.quantity',$loop->first ? min($row['quantity'],(float)$lot->quantity_on_hand) : 0) }}" class="ml-auto block w-40 rounded-lg border border-slate-300 px-3 py-2 text-right"></td></tr>
     @empty
     <tr data-shortage-row="{{ $row['item_id'] }}"><td colspan="5" class="px-4 py-5">
      <div class="mx-auto max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-left">
       <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-bold text-amber-900">Hiện kho đang hết hàng</p><p class="mt-1 text-sm text-amber-800">Mặt hàng chưa có lô tồn khả dụng. Có thể ghi nhận chờ cung cấp để vẫn ghi sổ các mặt hàng đang có tồn.</p></div>
       <button type="button" data-defer-toggle="{{ $row['item_id'] }}" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100">Ghi nhận chờ cung cấp</button></div>
       <div data-defer-panel="{{ $row['item_id'] }}" class="mt-4 hidden grid gap-3 border-t border-amber-200 pt-4 md:grid-cols-[180px_minmax(0,1fr)]">
        <input type="hidden" data-defer-enabled="{{ $row['item_id'] }}" name="deferred[{{ $row['item_id'] }}][enabled]" value="0">
        <label class="text-xs font-bold text-slate-600">Dự kiến cung cấp lại<input type="date" name="deferred[{{ $row['item_id'] }}][expected_supply_date]" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"></label>
        <label class="text-xs font-bold text-slate-600">Ghi chú *<input type="text" name="deferred[{{ $row['item_id'] }}][note]" value="Hiện kho đang hết hàng. Đơn hàng dự kiến cung cấp lại." class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" maxlength="2000"></label>
        <p class="md:col-span-2 text-xs text-slate-500">Phần thiếu không trừ tồn kho. Nhật ký này được lưu cùng phiếu để người lên đơn nhận biết và theo dõi cấp bổ sung.</p>
       </div>
      </div>
     </td></tr>
     @endforelse
    </tbody></table></div>@else<div class="px-5 py-4 text-sm text-slate-500">Bạn có thể chỉnh sửa phiếu nháp. Chọn lô và ghi sổ yêu cầu quyền <b>phê duyệt xuất kho</b>.</div>@endif
   </section>
  @endforeach
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><label class="text-sm font-semibold">Ghi chú<textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('notes',$issue->notes) }}</textarea></label><div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-500">FEFO là gợi ý ưu tiên. Có thể chia một thuốc qua nhiều lô; tổng SL lô phải bằng <b>SL duyệt</b>.</p><div class="flex flex-wrap gap-2"><a href="{{ route('admin.pharma.inventory.issues.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Hủy</a><button type="submit" form="bid-draft-form" class="rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Lưu phiếu nháp</button>@if($canApprove)<button id="bid-post-button" type="submit" form="bid-draft-form" formaction="{{ route('admin.pharma.inventory.issues.bid-sales.post',$issue) }}" formmethod="POST" data-stock-ready="{{ $stockReady ? '1' : '0' }}" data-has-postable-stock="{{ $hasPostableStock ? '1' : '0' }}" @disabled(!$stockReady) class="rounded-xl px-5 py-2.5 text-sm font-bold text-white {{ $stockReady ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300 text-slate-500' }}" @if(!$stockReady) title="Chưa thể duyệt vì có mặt hàng chưa có lô tồn khả dụng" @endif>Duyệt & ghi sổ</button>@endif</div></div></section>
 </form>
</div>
@if($addableAllocations->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', () => {
 const panel=document.getElementById('bid-add-panel'), toggle=document.getElementById('toggle-bid-add');
 const select=document.getElementById('bid-add-allocation'), qty=document.getElementById('bid-add-quantity');
 const pending=document.getElementById('bid-pending-products'), count=document.getElementById('bid-product-count'), postButton=document.getElementById('bid-post-button');
 toggle?.addEventListener('click',()=>panel.classList.toggle('hidden'));
 if(select && window.TomSelect) new TomSelect(select,{create:false,allowEmptyOption:true,placeholder:'Tìm mã hoặc tên thuốc...'});
 const refreshCount=()=>{ if(count) count.textContent=(Number(count.dataset.baseCount||0)+pending.children.length)+' sản phẩm'; };
 const refreshPostState=()=>{
  if(!postButton) return;
  const hasPending=pending.children.length>0, hasPostableStock=postButton.dataset.hasPostableStock==='1';
  const unresolved=[...document.querySelectorAll('[data-shortage-row]')].some(row=>{
   const id=row.dataset.shortageRow, enabled=document.querySelector('[data-defer-enabled="'+id+'"]');
   return !enabled || enabled.value!=='1';
  });
  postButton.disabled=hasPending || !hasPostableStock || unresolved;
  postButton.classList.toggle('bg-emerald-600',!postButton.disabled);
  postButton.classList.toggle('hover:bg-emerald-700',!postButton.disabled);
  postButton.classList.toggle('bg-slate-300',postButton.disabled);
  postButton.classList.toggle('text-slate-500',postButton.disabled);
  postButton.classList.toggle('cursor-not-allowed',postButton.disabled);
  postButton.title=hasPending ? 'Hãy lưu phiếu nháp để hệ thống tải tồn kho/lô thực tế trước khi duyệt' : (!hasPostableStock ? 'Chưa có hàng thực xuất để ghi sổ' : (unresolved ? 'Hãy ghi nhận chờ cung cấp cho các mặt hàng đang thiếu tồn' : ''));
 };
 document.querySelectorAll('[data-defer-toggle]').forEach(button=>button.addEventListener('click',()=>{
  const id=button.dataset.deferToggle, panel=document.querySelector('[data-defer-panel="'+id+'"]'), enabled=document.querySelector('[data-defer-enabled="'+id+'"]');
  const active=enabled.value!=='1'; enabled.value=active?'1':'0'; panel.classList.toggle('hidden',!active);
  button.textContent=active?'Đã ghi nhận chờ cung cấp':'Ghi nhận chờ cung cấp';
  button.classList.toggle('bg-amber-100',active); refreshPostState();
 }));
 refreshPostState();
 document.getElementById('bid-add-button')?.addEventListener('click',()=>{
  const id=select.value, option=select.options[select.selectedIndex], quantity=parseFloat(qty.value||'0'), max=parseFloat(option?.dataset.max||'0');
  if(!id){ alert('Vui lòng chọn sản phẩm trúng thầu.'); return; }
  if(!(quantity>0) || quantity>max){ alert('Số lượng thêm phải lớn hơn 0 và không vượt phân bổ còn lại.'); return; }
  if(pending.querySelector('[data-allocation-id="'+id+'"]')){ alert('Sản phẩm này đã được chọn thêm.'); return; }
  const text=option.textContent.trim(), parts=text.split(' · '), code=parts[0]||'', name=parts[1]||'', remain=(parts[2]||'').replace(/^Còn\s*/,'');
  const price=(parts[3]||'').replace(/\s*đ$/,''); const contract=parts[4]||'';
  const card=document.createElement('section'); card.dataset.allocationId=id; card.className='overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm ring-1 ring-indigo-50';
  card.innerHTML='<input type="hidden" name="add_allocations[]" value="'+id+'"><input type="hidden" name="add_quantities['+id+']" value="'+quantity+'">'+
   '<div class="border-b border-indigo-100 bg-indigo-50/40 px-5 py-4"><div class="flex flex-wrap items-start justify-between gap-3"><div>'+
   '<div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-bold text-indigo-700">'+code+'</span><span class="rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-700">Mới thêm</span></div>'+
   '<h2 class="mt-1 font-bold text-slate-900">'+name+'</h2><div class="mt-1 text-xs text-slate-500">'+contract+'</div></div>'+
   '<button type="button" data-remove-pending class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">Bỏ</button></div>'+
   '<div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">'+
   '<div class="rounded-xl bg-white p-3 text-xs text-slate-500">Còn phân bổ<div class="mt-1 text-sm font-bold text-emerald-700">'+remain+'</div></div>'+
   '<div class="rounded-xl bg-white p-3 text-xs text-slate-500">Đơn giá trúng thầu<div class="mt-1 text-sm font-bold text-slate-900">'+price+' đ</div></div>'+
   '<div class="rounded-xl bg-white p-3 text-xs text-slate-500">Trạng thái<div class="mt-1 text-sm font-bold text-indigo-700">Chờ lưu nháp</div></div>'+
   '<div class="rounded-xl border border-indigo-200 bg-white p-3 text-xs font-bold text-indigo-800">SL thêm<div class="mt-1 text-right text-sm font-bold text-slate-900">'+quantity.toLocaleString('vi-VN')+'</div></div></div></div>'+
   '<div class="px-5 py-3 text-xs text-slate-500">Sau khi lưu phiếu nháp, hệ thống sẽ tải tồn kho/lô thực tế và áp dụng quy trình phê duyệt như các sản phẩm khác.</div>';
  card.querySelector('[data-remove-pending]').addEventListener('click',()=>{card.remove(); refreshCount(); refreshPostState();});
  pending.appendChild(card); refreshCount(); refreshPostState(); qty.value=''; panel.classList.add('hidden');
 });
});
</script>
@endif
@endsection
