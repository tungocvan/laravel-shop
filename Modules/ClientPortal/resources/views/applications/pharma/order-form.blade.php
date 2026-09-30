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
<div class="min-h-[calc(100vh-5rem)] bg-slate-50 pb-28" data-order-authoring>
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border">
        <div class="relative flex items-center justify-center">
            <a href="{{ $editing ? route('client.pharma.orders.show',$issue) : route('client.pharma.orders') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl">←</a>
            <h1 class="px-12 text-center text-xl font-black text-slate-950">{{ $editing ? 'Sửa đơn hàng' : 'Thêm mới đơn hàng' }}</h1>
        </div>
    </header>

    @if($errors->any())
        <div class="mx-auto mt-4 max-w-4xl rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <form id="order-form" method="POST" action="{{ $editing ? route('client.pharma.orders.update',$issue) : route('client.pharma.orders.store') }}" class="mx-auto mt-4 max-w-4xl space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Nguồn đơn hàng</p>
            <div class="mt-3 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5">
                <label class="cursor-pointer"><input class="peer sr-only" type="radio" name="source" value="price_list" @checked($currentSource==='price_list') @disabled($editing)><span class="block rounded-xl px-3 py-3 text-center text-sm font-black text-slate-600 peer-checked:bg-white peer-checked:text-slate-950 peer-checked:shadow-sm">Theo bảng giá</span></label>
                <label class="cursor-pointer"><input class="peer sr-only" type="radio" name="source" value="bid" @checked($currentSource==='bid') @disabled($editing)><span class="block rounded-xl px-3 py-3 text-center text-sm font-black text-slate-600 peer-checked:bg-white peer-checked:text-slate-950 peer-checked:shadow-sm">Theo kết quả trúng thầu</span></label>
            </div>
            @if($editing)<input type="hidden" name="source" value="{{ $currentSource }}">@endif
            <label class="mt-5 block"><span class="mb-2 block text-sm font-black text-slate-800">Ngày lập đơn</span><input type="date" name="issue_date" value="{{ old('issue_date',$issueDate) }}" class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base focus:border-slate-500 focus:ring-2 focus:ring-slate-200"></label>
        </section>

        <section id="price-list-source" class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Bảng giá</span>
                    <select id="price-list-select" name="price_list_id" class="h-14 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base">
                        <option value="">Chọn bảng giá</option>
                        @foreach($priceLists as $priceList)<option value="{{ $priceList->id }}" data-partner="{{ $priceList->partner_id }}" @selected((int)old('price_list_id',$issue?->price_list_id)===$priceList->id)>{{ $priceList->name }}{{ $priceList->partner ? ' · '.$priceList->partner->name : '' }}</option>@endforeach
                    </select>
                </label>
                <label class="mt-4 block"><span class="mb-2 block text-sm font-black text-slate-800">Khách hàng</span>
                    <input id="customer-search" type="search" placeholder="Tìm khách hàng..." class="mb-2 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base">
                    <select id="customer-select" name="recipient_partner_id" size="5" class="w-full rounded-2xl border border-slate-300 bg-white p-2 text-sm">
                        @foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((int)old('recipient_partner_id',$issue?->recipient_partner_id)===$customer->id)>{{ $customer->name }}{{ $customer->tax_code ? ' · '.$customer->tax_code : '' }}</option>@endforeach
                    </select>
                </label>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Sản phẩm theo bảng giá</h2>
                <p class="mt-1 text-sm text-slate-500">Đơn giá lấy trực tiếp từ bảng giá canonical; không nhận giá từ trình duyệt.</p>
                <div class="mt-4 space-y-3">
                    @foreach($priceLists as $priceList)
                        @foreach($priceList->items as $item)
                            @php $current=$currentByMedicine->get($item->medicine_id); @endphp
                            <article data-price-item data-price-list="{{ $priceList->id }}" class="hidden rounded-2xl border border-slate-200 p-4">
                                <div class="flex justify-between gap-3"><div><p class="font-black text-slate-950">{{ $item->medicine?->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->medicine?->medicine_code }} · {{ $item->medicine?->unit }}</p></div><p class="shrink-0 text-sm font-black">{{ $money($item->company_sale_price) }}</p></div>
                                <label class="mt-3 block"><span class="text-xs font-bold text-slate-500">Số lượng</span><input name="quantities[{{ $item->id }}]" value="{{ old('quantities.'.$item->id,$current?->quantity) }}" inputmode="decimal" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="0"></label>
                            </article>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>

        <section id="bid-source" class="hidden space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Sản phẩm trúng thầu được phân công</h2>
                <p class="mt-1 text-sm text-slate-500">Chỉ hiển thị bệnh viện/sản phẩm thuộc phân công của bạn. Đơn giá là giá trúng thầu canonical.</p>
                <input id="bid-search" type="search" placeholder="Tìm bệnh viện / sản phẩm..." class="mt-4 h-12 w-full rounded-2xl border border-slate-300 px-4">
                <div class="mt-4 space-y-3">
                    @foreach($bidRows as $row)
                        @php $current=$currentByAllocation->get($row->allocation_id); @endphp
                        <article data-bid-item data-search="{{ mb_strtolower($row->partner_name.' '.$row->medicine_name) }}" class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-bold text-slate-500">{{ $row->partner_name }}</p>
                            <div class="mt-1 flex justify-between gap-3"><div><p class="font-black text-slate-950">{{ $row->medicine_name }}</p><p class="text-xs text-slate-500">Còn {{ rtrim(rtrim(number_format($row->remaining_quantity,3,'.',''),'0'),'.') }} {{ $row->unit }}</p></div><p class="shrink-0 text-sm font-black">{{ $money($row->unit_price) }}</p></div>
                            <label class="mt-3 block"><span class="text-xs font-bold text-slate-500">Số lượng đặt</span><input name="quantities[{{ $row->allocation_id }}]" value="{{ old('quantities.'.$row->allocation_id,$current?->quantity) }}" inputmode="decimal" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="0"></label>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Ghi chú</span><textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3">{{ old('notes',$issue?->notes) }}</textarea></label>
        </section>

        <div class="sticky bottom-0 z-20 -mx-4 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:mx-0 lg:rounded-2xl lg:border">
            <div class="mx-auto grid max-w-4xl grid-cols-2 gap-3"><a href="{{ $editing ? route('client.pharma.orders.show',$issue) : route('client.pharma.orders') }}" class="flex h-13 items-center justify-center rounded-2xl border border-slate-300 font-black text-slate-700">Quay lại</a><button type="submit" class="h-13 rounded-2xl bg-slate-950 font-black text-white disabled:opacity-60">Lưu nháp</button></div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const sourceInputs=[...document.querySelectorAll('input[name="source"]')], price=document.getElementById('price-list-source'), bid=document.getElementById('bid-source');
 const selectedSource=()=>document.querySelector('input[name="source"]:checked')?.value || document.querySelector('input[type="hidden"][name="source"]')?.value || 'price_list';
 const syncSource=()=>{const isBid=selectedSource()==='bid';price?.classList.toggle('hidden',isBid);bid?.classList.toggle('hidden',!isBid);};
 sourceInputs.forEach(el=>el.addEventListener('change',syncSource));syncSource();
 const priceSelect=document.getElementById('price-list-select');
 const syncPrice=()=>document.querySelectorAll('[data-price-item]').forEach(el=>el.classList.toggle('hidden',el.dataset.priceList!==priceSelect?.value));
 priceSelect?.addEventListener('change',syncPrice);syncPrice();
 const customerSearch=document.getElementById('customer-search'), customerSelect=document.getElementById('customer-select');
 customerSearch?.addEventListener('input',()=>{const q=customerSearch.value.toLocaleLowerCase('vi');[...customerSelect.options].forEach(o=>o.hidden=!o.text.toLocaleLowerCase('vi').includes(q));});
 const bidSearch=document.getElementById('bid-search');
 bidSearch?.addEventListener('input',()=>{const q=bidSearch.value.toLocaleLowerCase('vi');document.querySelectorAll('[data-bid-item]').forEach(el=>el.classList.toggle('hidden',!el.dataset.search.includes(q)));});
});
</script>
@endsection
