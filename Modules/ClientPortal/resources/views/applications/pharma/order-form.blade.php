@extends('ClientPortal::layouts.application')

@section('title', $issue ? 'Sửa đơn hàng' : 'Thêm mới đơn hàng')
@section('app-name', $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $editing = $issue !== null;
    $currentSource = old('source', $editing && ($issue->issue_source ?? 'normal') === 'bid' ? 'bid' : 'price_list');
    $currentByMedicine = $editing ? $issue->items->keyBy('medicine_id') : collect();
    $currentByAllocation = $editing ? $issue->items->whereNotNull('drug_bid_award_allocation_id')->keyBy('drug_bid_award_allocation_id') : collect();
    $money = fn($value) => number_format((float)$value, 0, ',', '.').' đ';
@endphp
<div class="min-w-0 max-w-full overflow-x-hidden bg-slate-50 pb-32" data-order-authoring>
<header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-auto lg:max-w-7xl lg:rounded-3xl lg:border">
 <div class="relative flex items-center justify-center"><a href="{{ $editing ? route('client.pharma.orders.show',$issue) : route('client.pharma.orders') }}" class="absolute left-0 flex h-11 w-11 items-center justify-center rounded-full text-2xl">←</a><div class="text-center"><h1 class="text-xl font-black text-slate-950">{{ $editing ? 'Sửa đơn hàng' : 'Thêm mới đơn hàng' }}</h1><p class="mt-1 text-xs font-semibold text-slate-500">Lập đơn đúng phạm vi User · giá lấy từ nguồn canonical</p></div></div>
</header>
@if($errors->any())<div class="mx-auto mt-4 max-w-7xl rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

<form id="order-form" method="POST" action="{{ $editing ? route('client.pharma.orders.update',$issue) : route('client.pharma.orders.store') }}" class="mx-auto mt-4 grid max-w-7xl gap-4 lg:grid-cols-[360px_minmax(0,1fr)] lg:items-start">
@csrf @if($editing) @method('PUT') @endif
<aside class="space-y-4 lg:sticky lg:top-4">
 <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
  <p class="text-xs font-black uppercase tracking-wide text-slate-500">Thiết lập đơn hàng</p>
  <div class="mt-4" data-order-source-picker>
   <div class="grid grid-cols-2 gap-2">
    <label class="min-w-0 cursor-pointer"><input class="peer sr-only" type="radio" name="source" value="price_list" @checked($currentSource==='price_list') @disabled($editing)><span class="flex min-h-16 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 text-center text-xs font-black text-slate-500 transition active:scale-[.985] peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:shadow-md peer-checked:ring-2 peer-checked:ring-indigo-100"><span class="text-base">▤</span><span class="whitespace-nowrap">Theo bảng giá</span><span class="hidden rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] text-white peer-checked:inline">✓</span></span></label>
    <label class="min-w-0 cursor-pointer"><input class="peer sr-only" type="radio" name="source" value="bid" @checked($currentSource==='bid') @disabled($editing)><span class="flex min-h-16 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 text-center text-xs font-black text-slate-500 transition active:scale-[.985] peer-checked:border-slate-950 peer-checked:bg-slate-950 peer-checked:text-white peer-checked:shadow-md peer-checked:ring-2 peer-checked:ring-slate-200"><span class="text-base">◎</span><span class="whitespace-nowrap">Theo trúng thầu</span><span class="hidden rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] text-white peer-checked:inline">✓</span></span></label>
   </div>
   <p id="order-source-hint" class="px-2 pb-1 pt-2 text-center text-[11px] font-semibold text-slate-500">Giá bán theo bảng giá đang hiệu lực</p>
  </div>@if($editing)<input type="hidden" name="source" value="{{ $currentSource }}">@endif
  <div id="manager-context" class="mt-4">
  @if($canCreateForUser)
   <div data-manager-combobox><span class="mb-2 block text-sm font-black">Người phụ trách</span>
    <div class="relative">
     <button id="manager-toggle" type="button" class="flex h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 text-left"><span id="manager-label" class="min-w-0 truncate text-sm text-slate-700">Chọn User phụ trách</span><span class="ml-2 shrink-0 text-slate-400">⌄</span></button>
     <div id="manager-panel" class="absolute z-50 mt-1 hidden w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
      <input id="manager-search" type="search" autocomplete="off" placeholder="Tìm User phụ trách..." class="h-11 w-full rounded-xl border border-slate-300 px-3">
      <div id="manager-results" class="mt-2 max-h-56 overflow-y-auto"></div>
     </div>
    </div>
    <input id="manager-id" type="hidden" name="manager_user_id" value="{{ old('manager_user_id',$managerUserId) }}">
    <p class="mt-2 text-xs text-slate-500">Bạn đang lên đơn thay User; hệ thống vẫn lưu người tạo thực tế để audit.</p>
   </div>
  @else
   <input type="hidden" name="manager_user_id" value="{{ $managerUserId }}"><div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Người phụ trách</p><p class="mt-1 font-black">{{ $orderManagers->first()?->name }}</p></div>
  @endif
  </div>
  <label class="mt-4 block"><span class="mb-2 block text-sm font-black">Ngày lập đơn</span><input type="date" name="issue_date" value="{{ old('issue_date',$issueDate) }}" class="h-12 w-full rounded-2xl border border-slate-300 px-4"></label>
 </section>

 <section id="price-list-context" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
  @if($priceLists->isEmpty())
   <div id="price-list-unassigned-warning" class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
    <p class="text-sm font-black text-amber-900">Bạn chưa được phân công bảng giá đang hiệu lực</p>
    <p class="mt-1 text-xs leading-5 text-amber-800">Không thể lập đơn theo bảng giá. Vui lòng liên hệ người quản lý để được phân công bảng giá phù hợp.</p>
   </div>
  @endif
  <label class="block"><span class="mb-2 block text-sm font-black">Bảng giá của User</span><select id="price-list-select" name="price_list_id" class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-3"><option value="">Chọn bảng giá</option>@foreach($priceLists as $pl)<option value="{{ $pl->id }}" data-partner="{{ $pl->partner_id }}" @selected((int)old('price_list_id',$issue?->price_list_id)===$pl->id)>{{ $pl->name }}{{ $pl->partner ? ' · '.$pl->partner->name : '' }}</option>@endforeach</select></label>
  <label class="mt-4 block"><span class="mb-2 block text-sm font-black">Khách hàng</span>
   <div class="relative"><input id="customer-search" type="search" autocomplete="off" placeholder="Tìm tên / MST khách hàng..." class="h-12 w-full rounded-2xl border border-slate-300 px-4"><div id="customer-results" class="absolute z-40 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl"></div></div>
   <input id="customer-id" type="hidden" name="recipient_partner_id" value="{{ old('recipient_partner_id',$issue?->recipient_partner_id) }}">
   <p id="customer-selected" class="mt-2 min-h-5 text-xs font-bold text-slate-600"></p>
  </label>
 </section>
</aside>

<main class="min-w-0 space-y-4">
 <section id="price-list-products" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="flex items-end justify-between gap-3"><div><h2 class="text-lg font-black">Sản phẩm theo bảng giá</h2><p class="mt-1 text-sm text-slate-500">Chọn sản phẩm, nhập số lượng rồi thêm vào đơn.</p></div><span id="product-count" class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-black">0 đã thêm</span></div>
  <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3" data-product-picker>
   <div class="relative">
    <button id="product-toggle" type="button" class="flex h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 text-left"><span id="product-picker-label" class="min-w-0 truncate text-sm text-slate-500">Chọn sản phẩm từ bảng giá...</span><span class="ml-2 shrink-0 text-slate-400">⌄</span></button>
    <div id="product-panel" class="absolute z-50 mt-1 hidden w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
     <input id="product-search" type="search" autocomplete="off" placeholder="Tìm tên thuốc / mã thuốc / hoạt chất..." class="h-11 w-full rounded-xl border border-slate-300 px-3">
     <div id="product-results" class="mt-2 max-h-64 overflow-y-auto"></div>
    </div>
   </div>
   <div id="product-add-panel" class="mt-3 hidden grid-cols-[minmax(0,1fr)_110px] gap-2">
    <div class="min-w-0 rounded-xl bg-white px-3 py-2"><p id="product-selected-name" class="truncate text-sm font-black"></p><p id="product-selected-meta" class="mt-0.5 truncate text-xs text-slate-500"></p></div>
    <input id="product-add-qty" inputmode="decimal" class="h-12 w-full rounded-xl border border-slate-300 px-3 text-center" placeholder="Số lượng">
    <button id="product-add" type="button" class="col-span-2 h-11 rounded-xl bg-slate-950 text-sm font-black text-white">+ Thêm vào đơn</button>
   </div>
  </div>
  <div id="selected-products" class="mt-4 space-y-3">
   @foreach($priceLists as $pl) @foreach($pl->items as $item) @php $current=((int)($issue?->price_list_id ?? 0)===(int)$pl->id) ? $currentByMedicine->get($item->medicine_id) : null; @endphp
   <article data-price-item data-item-id="{{ $item->id }}" data-price-list="{{ $pl->id }}" data-search="{{ mb_strtolower(($item->medicine?->name ?? '').' '.($item->medicine?->medicine_code ?? '').' '.($item->medicine?->active_ingredient ?? '')) }}" data-name="{{ $item->medicine?->name }}" data-meta="{{ $item->medicine?->medicine_code }} · {{ $item->medicine?->unit }} · {{ $money($item->company_sale_price) }}" data-price="{{ (float)$item->company_sale_price }}" class="{{ $current && (float)$current->quantity>0 ? '' : 'hidden' }} min-w-0 rounded-2xl border border-slate-200 bg-white p-4">
    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2">
     <div class="min-w-0"><p class="truncate font-black">{{ $item->medicine?->name }}</p><p class="mt-0.5 truncate text-xs text-slate-500">{{ $item->medicine?->medicine_code }} · {{ $item->medicine?->unit }}</p></div>
     <p class="shrink-0 text-right text-sm font-black">{{ $money($item->company_sale_price) }}</p>
     <div class="col-span-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-2 sm:col-span-1 sm:border-0 sm:pt-0"><div class="flex items-center gap-2"><span class="whitespace-nowrap text-xs font-bold text-slate-500">SL</span><input aria-label="Số lượng" data-quantity name="quantities[{{ $item->id }}]" value="{{ old('quantities.'.$item->id,$current?->quantity) }}" inputmode="decimal" class="h-9 w-24 rounded-xl border border-slate-300 px-2 text-right text-sm font-black" placeholder="0"></div><button data-remove-product type="button" class="flex h-9 items-center rounded-xl px-2 text-xs font-black text-rose-600 hover:bg-rose-50">Xóa</button></div>
    </div>
   </article>
   @endforeach @endforeach
  </div>
  <div id="selected-products-empty" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Chưa có sản phẩm. Chọn sản phẩm phía trên để thêm vào đơn.</div>
 </section>

 <section id="bid-products" class="hidden space-y-4">
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
   <div><h2 class="text-lg font-black">Xuất bán hàng thầu</h2><p class="mt-1 text-sm text-slate-500">Chọn chủ đầu tư và bệnh viện trong phạm vi User phụ trách.</p></div>
   <div class="mt-4 grid gap-3 sm:grid-cols-2">
    <div data-bid-investor-combobox><span class="mb-2 block text-sm font-black">Chủ đầu tư *</span><div class="relative"><button id="bid-investor-toggle" type="button" class="flex h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 text-left"><span id="bid-investor-label" class="min-w-0 truncate text-sm text-slate-700">Chọn chủ đầu tư</span><span class="ml-2 shrink-0 text-slate-400">⌄</span></button><div id="bid-investor-panel" class="absolute z-50 mt-1 hidden w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"><input id="bid-investor-search" type="search" autocomplete="off" placeholder="Tìm chủ đầu tư..." class="h-11 w-full rounded-xl border border-slate-300 px-3"><div id="bid-investor-results" class="mt-2 max-h-56 overflow-y-auto"></div></div></div><input id="bid-investor" type="hidden" value=""></div>
    <div data-bid-partner-combobox><span class="mb-2 block text-sm font-black">Khách hàng / Bệnh viện *</span><div class="relative"><button id="bid-partner-toggle" type="button" disabled class="flex h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 text-left disabled:bg-slate-50 disabled:text-slate-400"><span id="bid-partner-label" class="min-w-0 truncate text-sm">Chọn chủ đầu tư trước</span><span class="ml-2 shrink-0 text-slate-400">⌄</span></button><div id="bid-partner-panel" class="absolute z-50 mt-1 hidden w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"><input id="bid-partner-search" type="search" autocomplete="off" placeholder="Tìm khách hàng / bệnh viện..." class="h-11 w-full rounded-xl border border-slate-300 px-3"><div id="bid-partner-results" class="mt-2 max-h-56 overflow-y-auto"></div></div></div><input id="bid-partner" type="hidden" value=""></div>
   </div>
  </section>
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
   <div class="flex items-end justify-between gap-3"><div><h2 class="text-lg font-black">Sản phẩm trúng thầu</h2><p class="mt-1 text-sm text-slate-500">Đơn giá và hạn mức lấy từ phân bổ canonical.</p></div><span id="bid-visible-count" class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-black">0 sản phẩm</span></div>
   <div data-bid-product-picker class="relative mt-4"><button id="bid-product-toggle" type="button" disabled class="flex h-12 w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 text-left disabled:bg-slate-50 disabled:text-slate-400"><span id="bid-product-label" class="min-w-0 truncate text-sm">Chọn chủ đầu tư và bệnh viện trước</span><span class="ml-2 shrink-0 text-slate-400">⌄</span></button><div id="bid-product-panel" class="absolute z-40 mt-1 hidden w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"><input id="bid-search" type="search" autocomplete="off" placeholder="Tìm mã thuốc / tên thuốc / hoạt chất..." class="h-11 w-full rounded-xl border border-slate-300 px-3"><div id="bid-product-results" class="mt-2 max-h-64 overflow-y-auto"></div></div></div>
   <div id="bid-empty" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Chọn chủ đầu tư và bệnh viện để tải sản phẩm được phân công.</div>
   <div class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-2">@foreach($bidRows as $row) @php $current=$currentByAllocation->get($row->allocation_id); $investorKey=$row->investor_code ?: $row->investor_name; @endphp
    <article data-bid-item data-investor="{{ $investorKey }}" data-investor-name="{{ $row->investor_name }}" data-partner="{{ $row->partner_id }}" data-partner-name="{{ $row->partner_name }}" data-search="{{ mb_strtolower(($row->medicine_code ?? '').' '.$row->medicine_name.' '.$row->partner_name) }}" data-price="{{ $row->unit_price }}" class="hidden rounded-2xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate font-black">{{ $row->medicine_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $row->medicine_code }} · {{ $row->unit }}</p><p class="mt-1 text-xs font-bold text-slate-600">SL phân bổ: {{ number_format($row->allocated_quantity, 0, ',', '.') }} · SL còn lại: {{ number_format($row->remaining_quantity, 0, ',', '.') }}</p></div><p class="shrink-0 text-sm font-black">{{ $money($row->unit_price) }}</p></div><div class="mt-3 flex items-center gap-2"><span class="text-xs font-bold text-slate-500">SL</span><input data-quantity name="quantities[{{ $row->allocation_id }}]" value="{{ old('quantities.'.$row->allocation_id,$current?->quantity) }}" inputmode="decimal" class="h-9 w-24 rounded-xl border border-slate-300 px-2 text-right text-sm font-black" placeholder="0"></div></article>
   @endforeach</div>
  </section>
 </section>
 <section class="rounded-3xl border border-slate-200 bg-white p-5"><label><span class="mb-2 block text-sm font-black">Ghi chú</span><textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('notes',$issue?->notes) }}</textarea></label></section>
</main>

<div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_30px_rgba(15,23,42,.08)] backdrop-blur lg:left-[calc(50%+130px)] lg:right-auto lg:bottom-6 lg:w-[568px] lg:-translate-x-1/2 lg:rounded-3xl lg:border lg:px-4">
 <div class="flex items-center gap-3">
  <div class="min-w-0 flex-1"><div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-black text-slate-600">Nháp</span><p id="order-summary" class="min-w-0 truncate text-sm font-black">0 sản phẩm · 0 SL · 0 đ</p></div></div>
  <a href="{{ $editing ? route('client.pharma.orders.show',$issue) : route('client.pharma.orders') }}" class="hidden h-11 w-24 shrink-0 items-center justify-center rounded-2xl border border-slate-300 font-black sm:flex">Hủy</a><button type="submit" class="h-11 w-32 shrink-0 rounded-2xl bg-slate-950 font-black text-white">Lưu nháp</button>
 </div>
 <a href="{{ $editing ? route('client.pharma.orders.show',$issue) : route('client.pharma.orders') }}" class="mt-2 flex h-11 items-center justify-center rounded-2xl border border-slate-300 font-black sm:hidden">Hủy</a>
</div>
</form></div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
 const managers=@json($orderManagers->map(fn($m)=>['id'=>$m->id,'name'=>$m->name,'email'=>$m->email])->values());
 const managerBox=document.querySelector('[data-manager-combobox]'), managerToggle=document.getElementById('manager-toggle'), managerPanel=document.getElementById('manager-panel'), managerSearch=document.getElementById('manager-search'), managerResults=document.getElementById('manager-results'), managerId=document.getElementById('manager-id'), managerLabel=document.getElementById('manager-label');
 const renderManagers=()=>{if(!managerResults)return;const q=(managerSearch?.value||'').toLocaleLowerCase('vi').trim();managerResults.innerHTML='';managers.filter(m=>(m.name+' '+(m.email||'')).toLocaleLowerCase('vi').includes(q)).slice(0,25).forEach(m=>{const b=document.createElement('button');b.type='button';b.className='block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100';b.textContent=m.name+(m.email?' · '+m.email:'');b.onclick=()=>{managerId.value=m.id;managerLabel.textContent=m.name+(m.email?' · '+m.email:'');managerPanel.classList.add('hidden');const u=new URL(location.href);u.searchParams.set('manager_user_id',m.id);location.href=u.toString();};managerResults.appendChild(b);});};
 const openManagers=()=>{managerPanel?.classList.remove('hidden');renderManagers();setTimeout(()=>managerSearch?.focus(),0);};
 managerToggle?.addEventListener('click',()=>{if(managerPanel.classList.contains('hidden'))openManagers();else managerPanel.classList.add('hidden');});
 managerSearch?.addEventListener('input',renderManagers);
 if(managerId?.value){const m=managers.find(x=>String(x.id)===String(managerId.value));if(m)managerLabel.textContent=m.name+(m.email?' · '+m.email:'');}

 const priceContext=document.getElementById('price-list-context'), priceProducts=document.getElementById('price-list-products'), bidProducts=document.getElementById('bid-products');
 const source=()=>document.querySelector('input[name="source"]:checked')?.value||document.querySelector('input[type="hidden"][name="source"]')?.value||'price_list';
 const syncSource=()=>{const b=source()==='bid';const hint=document.getElementById('order-source-hint');if(hint)hint.textContent=b?'Giá và số lượng theo phân bổ trúng thầu':'Giá bán theo bảng giá đang hiệu lực';document.getElementById('manager-context')?.classList.remove('hidden');priceContext.classList.toggle('hidden',b);priceProducts.classList.toggle('hidden',b);bidProducts.classList.toggle('hidden',!b);priceContext.querySelectorAll('input,select').forEach(el=>el.disabled=b);priceProducts.querySelectorAll('input').forEach(el=>el.disabled=b);bidProducts.querySelectorAll('input').forEach(el=>el.disabled=!b);summary();};
 document.querySelectorAll('input[name="source"]').forEach(x=>x.addEventListener('change',syncSource));

 const pl=document.getElementById('price-list-select'), productBox=document.querySelector('[data-product-picker]'), productToggle=document.getElementById('product-toggle'), productPanel=document.getElementById('product-panel'), productSearch=document.getElementById('product-search'), productResults=document.getElementById('product-results'), productAddPanel=document.getElementById('product-add-panel'), productAddQty=document.getElementById('product-add-qty');
 let pendingProduct=null;
 const eligibleProducts=()=>[...document.querySelectorAll('[data-price-item]')].filter(el=>el.dataset.priceList===pl.value);
 const syncSelectedProducts=()=>{let n=0;document.querySelectorAll('[data-price-item]').forEach(el=>{const eligible=el.dataset.priceList===pl.value,q=parseFloat(el.querySelector('[data-quantity]')?.value)||0;el.classList.toggle('hidden',!eligible||q<=0);if(eligible&&q>0)n++;});document.getElementById('product-count').textContent=n+' đã thêm';document.getElementById('selected-products-empty').classList.toggle('hidden',n>0);summary();};
 const chooseProduct=el=>{pendingProduct=el;document.getElementById('product-picker-label').textContent=el.dataset.name;document.getElementById('product-selected-name').textContent=el.dataset.name;document.getElementById('product-selected-meta').textContent=el.dataset.meta;productAddQty.value=el.querySelector('[data-quantity]')?.value||'';productAddPanel.classList.remove('hidden');productAddPanel.classList.add('grid');productPanel.classList.add('hidden');setTimeout(()=>productAddQty.focus(),0);};
 const renderProducts=()=>{const q=(productSearch.value||'').toLocaleLowerCase('vi').trim();productResults.innerHTML='';eligibleProducts().filter(el=>el.dataset.search.includes(q)).slice(0,30).forEach(el=>{const b=document.createElement('button');b.type='button';b.className='flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left hover:bg-slate-100';b.innerHTML='<span class="min-w-0"><strong class="block truncate text-sm"></strong><small class="block truncate text-slate-500"></small></span><span class="shrink-0 text-xs font-black"></span>';b.querySelector('strong').textContent=el.dataset.name;b.querySelector('small').textContent=el.dataset.meta;b.querySelector('span:last-child').textContent=(parseFloat(el.querySelector('[data-quantity]')?.value)||0)>0?'Đã thêm':'';b.onclick=()=>chooseProduct(el);productResults.appendChild(b);});if(!productResults.children.length)productResults.innerHTML='<p class="px-3 py-5 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</p>';};
 productToggle?.addEventListener('click',()=>{if(!pl.value)return;productPanel.classList.toggle('hidden');if(!productPanel.classList.contains('hidden')){renderProducts();setTimeout(()=>productSearch.focus(),0);}});
 productSearch?.addEventListener('input',renderProducts);
 document.getElementById('product-add')?.addEventListener('click',()=>{if(!pendingProduct)return;const q=parseFloat(productAddQty.value)||0;if(q<=0){productAddQty.focus();return;}pendingProduct.querySelector('[data-quantity]').value=q;pendingProduct=null;productAddQty.value='';productAddPanel.classList.add('hidden');productAddPanel.classList.remove('grid');document.getElementById('product-picker-label').textContent='Chọn sản phẩm từ bảng giá...';syncSelectedProducts();});
 document.querySelectorAll('[data-remove-product]').forEach(b=>b.addEventListener('click',()=>{const el=b.closest('[data-price-item]');el.querySelector('[data-quantity]').value='';syncSelectedProducts();}));
 pl?.addEventListener('change',()=>{pendingProduct=null;productPanel.classList.add('hidden');productAddPanel.classList.add('hidden');productAddPanel.classList.remove('grid');document.getElementById('product-picker-label').textContent='Chọn sản phẩm từ bảng giá...';document.querySelectorAll('[data-price-item]').forEach(el=>{if(el.dataset.priceList!==pl.value)el.querySelector('[data-quantity]').value='';});syncSelectedProducts();const partner=pl.selectedOptions[0]?.dataset.partner;if(partner){selectCustomer(partner,true);}else{document.getElementById('customer-search').readOnly=false;}});
 const customers=@json($customers->map(fn($c)=>['id'=>$c->id,'name'=>$c->name,'tax_code'=>$c->tax_code])->values());
 const cs=document.getElementById('customer-search'), cr=document.getElementById('customer-results'), cid=document.getElementById('customer-id'), csel=document.getElementById('customer-selected');
 window.selectCustomer=(id,locked=false)=>{const c=customers.find(x=>String(x.id)===String(id));if(!c)return;cid.value=c.id;cs.value=c.name;csel.textContent='Đã chọn: '+c.name;cs.readOnly=locked;cr.classList.add('hidden');};
 const renderCustomers=()=>{const q=cs.value.toLocaleLowerCase('vi').trim();cr.innerHTML='';customers.filter(c=>(c.name+' '+(c.tax_code||'')).toLocaleLowerCase('vi').includes(q)).slice(0,25).forEach(c=>{const b=document.createElement('button');b.type='button';b.className='block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100';b.textContent=c.name+(c.tax_code?' · '+c.tax_code:'');b.onclick=()=>selectCustomer(c.id);cr.appendChild(b);});cr.classList.toggle('hidden',cr.children.length===0);};
 cs?.addEventListener('input',()=>{cid.value='';csel.textContent='';renderCustomers();});cs?.addEventListener('focus',renderCustomers);if(cid?.value)selectCustomer(cid.value,false);
 document.addEventListener('click',e=>{if(managerBox&&!managerBox.contains(e.target))managerPanel?.classList.add('hidden');const customerBox=cs?.closest('.relative');if(customerBox&&!customerBox.contains(e.target))cr?.classList.add('hidden');if(productBox&&!productBox.contains(e.target))productPanel?.classList.add('hidden');});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){managerPanel?.classList.add('hidden');cr?.classList.add('hidden');productPanel?.classList.add('hidden');}});

 const bidInvestor=document.getElementById('bid-investor'),bidPartner=document.getElementById('bid-partner'),bidItems=[...document.querySelectorAll('[data-bid-item]')];
 const bidInvestorBox=document.querySelector('[data-bid-investor-combobox]'),bidInvestorToggle=document.getElementById('bid-investor-toggle'),bidInvestorPanel=document.getElementById('bid-investor-panel'),bidInvestorSearch=document.getElementById('bid-investor-search'),bidInvestorResults=document.getElementById('bid-investor-results'),bidInvestorLabel=document.getElementById('bid-investor-label');
 const bidPartnerBox=document.querySelector('[data-bid-partner-combobox]'),bidPartnerToggle=document.getElementById('bid-partner-toggle'),bidPartnerPanel=document.getElementById('bid-partner-panel'),bidPartnerSearch=document.getElementById('bid-partner-search'),bidPartnerResults=document.getElementById('bid-partner-results'),bidPartnerLabel=document.getElementById('bid-partner-label');
 const bidProductBox=document.querySelector('[data-bid-product-picker]'),bidProductToggle=document.getElementById('bid-product-toggle'),bidProductPanel=document.getElementById('bid-product-panel'),bidSearch=document.getElementById('bid-search'),bidProductResults=document.getElementById('bid-product-results'),bidProductLabel=document.getElementById('bid-product-label');
 const bidInvestors=()=>[...new Map(bidItems.map(el=>[el.dataset.investor,el.dataset.investorName||el.dataset.investor])).entries()];
 const bidPartners=()=>[...new Map(bidItems.filter(el=>el.dataset.investor===bidInvestor.value).map(el=>[el.dataset.partner,el.dataset.partnerName])).entries()];
 const closeBidPickers=()=>{bidInvestorPanel?.classList.add('hidden');bidPartnerPanel?.classList.add('hidden');bidProductPanel?.classList.add('hidden');};
 const renderBidInvestors=()=>{const q=(bidInvestorSearch.value||'').toLocaleLowerCase('vi').trim();bidInvestorResults.innerHTML='';bidInvestors().filter(([,t])=>t.toLocaleLowerCase('vi').includes(q)).slice(0,30).forEach(([v,t])=>{const b=document.createElement('button');b.type='button';b.className='block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100';b.textContent=t;b.onclick=()=>selectBidInvestor(v,t);bidInvestorResults.appendChild(b);});if(!bidInvestorResults.children.length)bidInvestorResults.innerHTML='<p class="px-3 py-4 text-center text-sm text-slate-500">Không tìm thấy chủ đầu tư.</p>';};
 const renderBidPartners=()=>{const q=(bidPartnerSearch.value||'').toLocaleLowerCase('vi').trim();bidPartnerResults.innerHTML='';bidPartners().filter(([,t])=>t.toLocaleLowerCase('vi').includes(q)).slice(0,30).forEach(([v,t])=>{const b=document.createElement('button');b.type='button';b.className='block w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-100';b.textContent=t;b.onclick=()=>selectBidPartner(v,t);bidPartnerResults.appendChild(b);});if(!bidPartnerResults.children.length)bidPartnerResults.innerHTML='<p class="px-3 py-4 text-center text-sm text-slate-500">Không tìm thấy bệnh viện.</p>';};
 const selectedBidItems=()=>bidItems.filter(el=>el.dataset.investor===bidInvestor.value&&el.dataset.partner===bidPartner.value);
 const renderBidProducts=()=>{const q=(bidSearch.value||'').toLocaleLowerCase('vi').trim();bidProductResults.innerHTML='';selectedBidItems().filter(el=>el.dataset.search.includes(q)).slice(0,30).forEach(el=>{const b=document.createElement('button');b.type='button';b.className='flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left hover:bg-slate-100';const name=el.querySelector('p.font-black')?.textContent?.trim()||'';const meta=el.querySelector('p.text-xs')?.textContent?.trim()||'';b.innerHTML='<span class="min-w-0"><strong class="block truncate text-sm"></strong><small class="block truncate text-slate-500"></small></span><span class="shrink-0 text-xs font-black"></span>';b.querySelector('strong').textContent=name;b.querySelector('small').textContent=meta;b.querySelector('span:last-child').textContent=(parseFloat(el.querySelector('[data-quantity]')?.value)||0)>0?'Đã thêm':'Chọn';b.onclick=()=>{el.classList.remove('hidden');bidProductPanel.classList.add('hidden');bidProductLabel.textContent=name;setTimeout(()=>el.querySelector('[data-quantity]')?.focus(),0);};bidProductResults.appendChild(b);});if(!bidProductResults.children.length)bidProductResults.innerHTML='<p class="px-3 py-4 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</p>';};
 const renderBidSelected=()=>{let n=0;bidItems.forEach(el=>{const q=parseFloat(el.querySelector('[data-quantity]')?.value)||0,scope=el.dataset.investor===bidInvestor.value&&el.dataset.partner===bidPartner.value;el.classList.toggle('hidden',!scope||q<=0);if(scope&&q>0)n++;});document.getElementById('bid-visible-count').textContent=n+' đã thêm';document.getElementById('bid-empty').classList.toggle('hidden',n>0||!bidPartner.value);bidProductToggle.disabled=!bidPartner.value;bidProductLabel.textContent=bidPartner.value?'Chọn sản phẩm trúng thầu...':'Chọn chủ đầu tư và bệnh viện trước';};
 const selectBidInvestor=(v,t)=>{bidInvestor.value=v;bidInvestorLabel.textContent=t;bidPartner.value='';bidPartnerLabel.textContent='Chọn khách hàng / bệnh viện';bidPartnerToggle.disabled=false;bidItems.forEach(el=>el.querySelector('[data-quantity]').value='');closeBidPickers();renderBidSelected();summary();};
 const selectBidPartner=(v,t)=>{bidPartner.value=v;bidPartnerLabel.textContent=t;bidItems.forEach(el=>{if(el.dataset.partner!==v)el.querySelector('[data-quantity]').value='';});closeBidPickers();renderBidSelected();summary();};
 const hydrateBidContext=()=>{const selected=bidItems.find(el=>(parseFloat(el.querySelector('[data-quantity]')?.value)||0)>0);if(selected){bidInvestor.value=selected.dataset.investor;bidInvestorLabel.textContent=selected.dataset.investorName||selected.dataset.investor;bidPartner.value=selected.dataset.partner;bidPartnerLabel.textContent=selected.dataset.partnerName;bidPartnerToggle.disabled=false;}renderBidSelected();};
 bidInvestorToggle?.addEventListener('click',()=>{const open=bidInvestorPanel.classList.contains('hidden');closeBidPickers();if(open){bidInvestorPanel.classList.remove('hidden');renderBidInvestors();setTimeout(()=>bidInvestorSearch.focus(),0);}});bidInvestorSearch?.addEventListener('input',renderBidInvestors);
 bidPartnerToggle?.addEventListener('click',()=>{if(bidPartnerToggle.disabled)return;const open=bidPartnerPanel.classList.contains('hidden');closeBidPickers();if(open){bidPartnerPanel.classList.remove('hidden');renderBidPartners();setTimeout(()=>bidPartnerSearch.focus(),0);}});bidPartnerSearch?.addEventListener('input',renderBidPartners);
 bidProductToggle?.addEventListener('click',()=>{if(bidProductToggle.disabled)return;const open=bidProductPanel.classList.contains('hidden');closeBidPickers();if(open){bidProductPanel.classList.remove('hidden');renderBidProducts();setTimeout(()=>bidSearch.focus(),0);}});bidSearch?.addEventListener('input',renderBidProducts);
 document.addEventListener('click',e=>{if(bidInvestorBox&&!bidInvestorBox.contains(e.target))bidInvestorPanel?.classList.add('hidden');if(bidPartnerBox&&!bidPartnerBox.contains(e.target))bidPartnerPanel?.classList.add('hidden');if(bidProductBox&&!bidProductBox.contains(e.target))bidProductPanel?.classList.add('hidden');});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeBidPickers();});
 bidItems.forEach(el=>el.querySelector('[data-quantity]')?.addEventListener('input',()=>{renderBidSelected();summary();}));hydrateBidContext();
 const summary=()=>{let count=0,qty=0,total=0;document.querySelectorAll('[data-quantity]:not(:disabled)').forEach(i=>{const q=parseFloat(i.value)||0;if(q>0){count++;qty+=q;total+=q*(parseFloat(i.closest('[data-price]')?.dataset.price)||0);i.closest('[data-price]')?.classList.add('ring-2','ring-slate-900');}else{i.closest('[data-price]')?.classList.remove('ring-2','ring-slate-900');}});document.getElementById('order-summary').textContent=count+' sản phẩm · '+qty.toLocaleString('vi-VN')+' SL · '+Math.round(total).toLocaleString('vi-VN')+' đ';};
 document.querySelectorAll('[data-quantity]').forEach(i=>i.addEventListener('input',summary));syncSource();syncSelectedProducts();summary();
});
</script>
@endsection
