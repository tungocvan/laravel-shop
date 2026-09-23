@extends('Admin::layouts.master')
@section('title','Lập phiếu xuất kho')
@section('content')
@php
    $issueMedicines=$availableBalances->pluck('medicine')->unique('id')->sortBy('name')->values();
    $balanceOptions=$availableBalances->map(fn($b)=>[
        'id'=>$b->id,'medicine_id'=>$b->medicine_id,'batch'=>$b->batch_number,
        'expiry'=>$b->expiry_date->format('Y-m-d'),'expiry_label'=>$b->expiry_date->format('d/m/Y'),
        'quantity'=>(float)$b->quantity_on_hand,
    ])->values();
    $priceListManagers=$customerPriceLists->pluck('manager')->filter()->unique('id')->sortBy('name')->values();
@endphp
<div class="w-full max-w-[1500px] space-y-6">
    <header>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
        <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="ml-4 text-sm font-semibold text-slate-600">Danh sách phiếu xuất</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">Lập phiếu xuất kho</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $warehouse->name }} · Chọn thuốc trước, sau đó chọn lô còn tồn theo hạn dùng gần nhất (FEFO).</p>
    </header>

    <form method="POST" action="{{ route('admin.pharma.inventory.issues.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-medium">Ngày xuất
                <input type="date" name="issue_date" value="{{ old('issue_date',now()->toDateString()) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
            <label class="text-sm font-medium">Khách hàng / nơi nhận
                <x-select-search id="issue-recipient" name="recipient_partner_id" placeholder="Tìm khách hàng / nơi nhận...">
                    <option value="">Chọn khách hàng</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @selected((string)old('recipient_partner_id')===(string)$partner->id)>{{ $partner->name }}{{ $partner->tax_code ? ' · MST '.$partner->tax_code : '' }}</option>
                    @endforeach
                </x-select-search>
                <input type="hidden" name="recipient_name" id="issue-recipient-name" value="{{ old('recipient_name') }}">
            </label>
            <label class="text-sm font-medium">Người phụ trách
                <select id="issue-price-manager" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                    <option value="">Chọn người phụ trách</option>
                    @foreach($priceListManagers as $manager)<option value="{{ $manager->id }}">{{ $manager->name }}</option>@endforeach
                </select>
                <span class="mt-1 block text-xs text-slate-500">Dùng để lọc các bảng giá CUSTOMER đang kích hoạt.</span>
            </label>
            <label class="text-sm font-medium">Bảng giá xuất <span class="text-rose-600">*</span>
                <select id="issue-price-list" name="price_list_id" required disabled class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                    <option value="">Chọn người phụ trách trước</option>
                </select>
                <span id="issue-price-list-hint" class="mt-1 block text-xs text-slate-500">Chỉ sử dụng phạm vi CUSTOMER · ACTIVE · còn hiệu lực.</span>
            </label>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">Chi tiết xuất theo lô</h2>
                    <p class="mt-1 text-xs text-slate-500">Tìm thuốc nhanh, chọn lô còn tồn; danh sách lô được xếp HSD gần nhất trước.</p>
                </div>
                <button type="button" id="add-issue-row" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">+ Thêm dòng</button>
            </div>
            <div class="mt-4 overflow-x-auto">
                <div class="min-w-[1260px]">
                    <div class="grid grid-cols-12 gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <div class="col-span-3">Tên thuốc / Mã thuốc</div>
                        <div class="col-span-3">Số lô · Hạn dùng · Tồn khả dụng</div>
                        <div class="col-span-1">SL xuất</div>
                        <div class="col-span-2">Đơn giá xuất</div>
                        <div class="col-span-2 text-right">Thành tiền</div>
                        <div class="col-span-1 text-right">Thao tác</div>
                    </div>
                    <div id="issue-items" class="space-y-3 pt-3"></div>
                </div>
            </div>
        </div>

        @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Lưu phiếu xuất nháp</button></div>
    </form>
</div>

<template id="issue-row-template">
    <div class="issue-item-row grid grid-cols-12 gap-3 rounded-xl border border-slate-200 p-3">
        <div class="col-span-3">
            <select class="issue-medicine-select w-full" required>
                <option value="">Chọn thuốc</option>
                @foreach($issueMedicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_code }} — {{ $medicine->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-span-3">
            <select data-field="balance_id" class="issue-balance-select min-h-11 w-full rounded-xl border border-slate-300 px-3" required disabled>
                <option value="">Chọn thuốc trước</option>
            </select>
        </div>
        <div class="col-span-1">
            <input type="number" step="0.001" min="0.001" data-field="quantity" required placeholder="Số lượng xuất" class="issue-quantity min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </div>
        <div class="col-span-2">
            <input type="number" step="0.01" min="0" data-field="unit_price" required value="0" placeholder="Đơn giá xuất" class="issue-unit-price min-h-11 w-full rounded-xl border border-slate-300 px-3 text-right">
            <p class="issue-price-source mt-1 text-[11px] text-slate-500">Chưa có Giá bán CT · có thể nhập tay</p>
        </div>
        <div class="col-span-2 flex items-center justify-end pr-2">
            <span class="issue-line-total text-sm font-semibold text-slate-800">0 đ</span>
        </div>
        <div class="col-span-1 flex items-center justify-end">
            <button type="button" class="remove-issue-row text-sm font-semibold text-rose-700">Xóa</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const balances = @json($balanceOptions);
    const priceCandidates = @json($issueSalePrices);
    const priceLists = @json($customerPriceLists->map(fn($list)=>[
        'id'=>$list->id,'code'=>$list->code,'name'=>$list->name,'manager_user_id'=>$list->manager_user_id,
        'partner_id'=>$list->partner_id,'effective_from'=>$list->effective_from?->format('Y-m-d'),
        'effective_to'=>$list->effective_to?->format('Y-m-d'),'priority'=>$list->priority,
    ])->values());
    const partners = @json($partners->map(fn($partner)=>['id'=>$partner->id,'name'=>$partner->name])->values());
    const recipientSelect = document.querySelector('[name="recipient_partner_id"]');
    const recipientName = document.getElementById('issue-recipient-name');
    const issueDate = document.querySelector('[name="issue_date"]');
    const managerSelect = document.getElementById('issue-price-manager');
    const priceListSelect = document.getElementById('issue-price-list');
    const priceListHint = document.getElementById('issue-price-list-hint');
    const container = document.getElementById('issue-items');
    const template = document.getElementById('issue-row-template');

    function renumberRows() {
        container.querySelectorAll('.issue-item-row').forEach((row,index) => {
            row.querySelectorAll('[data-field]').forEach((field) => field.name = `items[${index}][${field.dataset.field}]`);
        });
    }

    function isEffective(candidate,date) {
        const from=candidate.item_effective_from || candidate.list_effective_from;
        const to=candidate.item_effective_to || candidate.list_effective_to;
        return (!from || from<=date) && (!to || to>=date);
    }

    function resolveSalePrice(medicineId) {
        const selectedPriceListId=priceListSelect?.value || '';
        const date=issueDate?.value || new Date().toISOString().slice(0,10);
        return priceCandidates.find((candidate)=>
            String(candidate.price_list_id)===String(selectedPriceListId) &&
            String(candidate.medicine_id)===String(medicineId) &&
            candidate.price_list_type==='customer' &&
            isEffective(candidate,date)
        ) || null;
    }

    function priceListIsEffective(list,date) {
        return (!list.effective_from || list.effective_from<=date) && (!list.effective_to || list.effective_to>=date);
    }

    function refreshPriceLists() {
        const managerId=managerSelect.value;
        const partnerId=recipientSelect?.value || '';
        const date=issueDate?.value || new Date().toISOString().slice(0,10);
        const current=priceListSelect.value;
        const lists=priceLists.filter((list)=>
            String(list.manager_user_id)===String(managerId) &&
            priceListIsEffective(list,date) &&
            (!list.partner_id || String(list.partner_id)===String(partnerId))
        );
        priceListSelect.innerHTML='<option value="">Chọn bảng giá CUSTOMER</option>';
        lists.forEach((list)=>{
            const option=document.createElement('option');
            option.value=list.id;
            option.textContent=`${list.code} — ${list.name}${list.partner_id ? ' · riêng khách hàng' : ' · dùng chung'}`;
            priceListSelect.appendChild(option);
        });
        priceListSelect.disabled=!managerId || lists.length===0;
        if(lists.some((list)=>String(list.id)===String(current))) priceListSelect.value=current;
        priceListHint.textContent=lists.length
            ? `${lists.length} bảng giá CUSTOMER · ACTIVE · còn hiệu lực.`
            : (managerId ? 'Không có bảng giá CUSTOMER phù hợp với ngày xuất / khách hàng.' : 'Chọn người phụ trách để tải bảng giá CUSTOMER.');
        refreshRowsForPriceList();
    }

    function medicineIdsForSelectedPriceList() {
        const date=issueDate?.value || new Date().toISOString().slice(0,10);
        return [...new Set(priceCandidates.filter((candidate)=>
            String(candidate.price_list_id)===String(priceListSelect.value) &&
            candidate.price_list_type==='customer' && isEffective(candidate,date)
        ).map((candidate)=>String(candidate.medicine_id)))];
    }

    function refreshRowsForPriceList() {
        const allowed=medicineIdsForSelectedPriceList();
        container.querySelectorAll('.issue-item-row').forEach((row)=>{
            const tom=row._medicineTom;
            if(!tom) return;
            const current=tom.getValue();
            tom.clear(true);
            tom.clearOptions();
            @foreach($issueMedicines as $medicine)
            if(allowed.includes('{{ $medicine->id }}')) tom.addOption({value:'{{ $medicine->id }}',text:@json($medicine->medicine_code.' — '.$medicine->name)});
            @endforeach
            tom.refreshOptions(false);
            tom.disable();
            if(priceListSelect.value){
                tom.enable();
                if(allowed.includes(String(current))) tom.setValue(current,true);
            }
            fillLots(row,tom.getValue());
            refreshPrice(row);
        });
    }

    function refreshPrice(row) {
        const medicineId=row.querySelector('.issue-medicine-select')?.value;
        const input=row.querySelector('.issue-unit-price');
        const source=row.querySelector('.issue-price-source');
        const candidate=resolveSalePrice(medicineId);
        const price=candidate?.company_sale_price === null || candidate?.company_sale_price === undefined ? 0 : Number(candidate.company_sale_price);
        input.value=Number.isFinite(price) ? price : 0;
        source.textContent=candidate
            ? `Giá bán CT · ${candidate.price_list_code || candidate.price_list_name || 'Bảng giá active'}${price===0?' · giá 0, có thể nhập tay':''}`
            : 'Chưa có Giá bán CT · mặc định 0, có thể nhập tay';
        updateLineTotal(row);
    }

    function updateLineTotal(row) {
        const quantity=Number(row.querySelector('.issue-quantity')?.value || 0);
        const price=Number(row.querySelector('.issue-unit-price')?.value || 0);
        row.querySelector('.issue-line-total').textContent=new Intl.NumberFormat('vi-VN',{maximumFractionDigits:0}).format(quantity*price)+' đ';
    }

    function refreshAllPrices() {
        container.querySelectorAll('.issue-item-row').forEach(refreshPrice);
    }

    function fillLots(row, medicineId) {
        const lotSelect=row.querySelector('.issue-balance-select');
        lotSelect.innerHTML='<option value="">Chọn lô còn tồn</option>';
        const matching=balances.filter((item)=>String(item.medicine_id)===String(medicineId));
        matching.forEach((item)=>{
            const option=document.createElement('option');
            option.value=item.id;
            option.textContent=`Lô ${item.batch} · HSD ${item.expiry_label} · Tồn ${new Intl.NumberFormat('vi-VN',{maximumFractionDigits:3}).format(item.quantity)}`;
            lotSelect.appendChild(option);
        });
        lotSelect.disabled=!medicineId || matching.length===0;
        if (medicineId && matching.length===0) lotSelect.innerHTML='<option value="">Không còn lô khả dụng</option>';
    }

    function addRow() {
        const fragment=template.content.cloneNode(true);
        const row=fragment.querySelector('.issue-item-row');
        container.appendChild(fragment);
        const medicineSelect=row.querySelector('.issue-medicine-select');
        const medicineTom=new TomSelect(medicineSelect,{
            plugins:['dropdown_input'],placeholder:'Tìm mã hoặc tên thuốc...',create:false,
            allowEmptyOption:true,dropdownParent:'body',
            onChange:(value)=>{ fillLots(row,value); refreshPrice(row); }
        });
        row._medicineTom=medicineTom;
        medicineTom.disable();
        if(priceListSelect.value) refreshRowsForPriceList();
        medicineSelect.addEventListener('change',(event)=>{ fillLots(row,event.target.value); refreshPrice(row); });
        row.querySelector('.issue-quantity').addEventListener('input',()=>updateLineTotal(row));
        row.querySelector('.issue-unit-price').addEventListener('input',()=>updateLineTotal(row));
        renumberRows();
    }

    document.getElementById('add-issue-row').addEventListener('click',addRow);
    recipientSelect?.addEventListener('change',()=>{
        const partner=partners.find((item)=>String(item.id)===String(recipientSelect.value));
        recipientName.value=partner?.name || '';
        refreshPriceLists();
    });
    managerSelect.addEventListener('change',refreshPriceLists);
    priceListSelect.addEventListener('change',refreshRowsForPriceList);
    issueDate?.addEventListener('change',refreshPriceLists);
    container.addEventListener('click',(event)=>{
        const button=event.target.closest('.remove-issue-row');
        if(!button || container.querySelectorAll('.issue-item-row').length===1) return;
        const row=button.closest('.issue-item-row');
        const select=row.querySelector('.issue-medicine-select');
        if(select?.tomselect) select.tomselect.destroy();
        row.remove(); renumberRows();
    });
    addRow();
});
</script>
@endsection
