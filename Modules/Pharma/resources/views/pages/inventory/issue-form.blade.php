@extends('Admin::layouts.master')
@section('title','Lập phiếu xuất kho')
@section('admin_container','full')
@section('content')
@php
    $issueMedicines=$availableBalances->pluck('medicine')->unique('id')->sortBy('name')->values();
    $balanceOptions=$availableBalances->map(fn($b)=>[
        'id'=>$b->id,'medicine_id'=>$b->medicine_id,'batch'=>$b->batch_number,
        'expiry'=>$b->expiry_date->format('Y-m-d'),'expiry_label'=>$b->expiry_date->format('d/m/Y'),
        'quantity'=>(float)$b->quantity_on_hand,
    ])->values();
@endphp
<div class="w-full space-y-6">
    <header>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
        <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="ml-4 text-sm font-semibold text-slate-600">Danh sách phiếu xuất</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">Lập phiếu xuất kho</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $warehouse->name }} · Chọn thuốc trước, sau đó chọn lô còn tồn theo hạn dùng gần nhất (FEFO).</p>
    </header>

    <form method="POST" action="{{ route('admin.pharma.inventory.issues.store') }}" class="space-y-5">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Thông tin phiếu</p><h2 class="mt-1 font-semibold text-slate-950">Thiết lập nhanh phiếu xuất</h2></div><span id="issue-price-badge" class="hidden rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"></span></div>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium">Ngày xuất
                    <input type="date" name="issue_date" value="{{ old('issue_date',now()->toDateString()) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                </label>
                <label class="text-sm font-medium">Người phụ trách <span class="text-rose-600">*</span>
                    <x-select-search id="issue-price-manager" name="manager_user_id" placeholder="Tìm người phụ trách...">
                        <option value="">Chọn người phụ trách</option>
                        @foreach($priceListManagers as $manager)<option value="{{ $manager->id }}" @selected((string)old('manager_user_id')===(string)$manager->id)>{{ $manager->name }}</option>@endforeach
                    </x-select-search>
                    <span class="mt-1 block text-xs text-slate-500">Chỉ hiện bảng giá được phân cho người này.</span>
                </label>
                <label class="text-sm font-medium">Bảng giá áp dụng <span class="text-rose-600">*</span>
                    <select id="issue-price-list" name="price_list_id" required disabled class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="">Chọn người phụ trách trước</option></select>
                    <span id="issue-price-list-hint" class="mt-1 block text-xs text-slate-500">Chọn người phụ trách để xem bảng giá phù hợp.</span>
                </label>
            </div>
            <div class="mt-5 border-t border-slate-100 pt-4">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div><p class="text-sm font-semibold text-slate-900">Khách hàng / nơi nhận</p><p class="text-xs text-slate-500">Tìm theo tên khách hàng, bệnh viện hoặc mã số thuế.</p></div>
                    <button type="button" id="issue-recipient-change" class="hidden rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700">Thay đổi</button>
                </div>
                <div id="issue-recipient-picker">
                    <x-select-search id="issue-recipient" name="recipient_partner_id" placeholder="Tìm tên khách hàng, bệnh viện, mã số thuế...">
                        <option value="">Chọn khách hàng / nơi nhận</option>
                        @foreach($partners as $partner)<option value="{{ $partner->id }}" @selected((string)old('recipient_partner_id')===(string)$partner->id)>{{ $partner->name }}{{ $partner->tax_code ? ' · MST '.$partner->tax_code : '' }}</option>@endforeach
                    </x-select-search>
                </div>
                <div id="issue-recipient-card" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-start gap-3"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sm font-bold text-indigo-700 ring-1 ring-slate-200">KH</div><div class="min-w-0"><p id="issue-recipient-card-name" class="font-semibold text-slate-950"></p><p id="issue-recipient-card-meta" class="mt-1 text-xs text-slate-500"></p></div></div>
                </div>
                <input type="hidden" name="recipient_name" id="issue-recipient-name" value="{{ old('recipient_name') }}">
            </div>
        </section>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Hàng hóa</p><h2 class="mt-1 font-semibold">Thuốc xuất kho</h2>
                    <p class="mt-1 text-xs text-slate-500">Tìm thuốc, hệ thống ưu tiên lô có hạn dùng gần nhất (FEFO).</p>
                </div>
                <div class="flex items-center gap-3"><span id="issue-item-count" class="text-sm font-semibold text-slate-500">0 sản phẩm</span><button type="button" id="add-issue-row" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">+ Thêm thuốc</button></div>
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
        <div class="sticky bottom-3 z-20 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white/95 px-5 py-4 shadow-lg backdrop-blur">
            <div class="flex items-baseline gap-5"><div><span id="issue-footer-count" class="text-sm font-semibold text-slate-700">0 sản phẩm</span><span id="issue-footer-quantity" class="ml-2 text-xs text-slate-500">· Tổng SL 0</span></div><div><span class="text-xs font-medium uppercase tracking-wide text-slate-500">Tổng tiền</span><strong id="issue-grand-total" class="ml-2 text-xl text-slate-950">0 đ</strong></div></div>
            <button id="issue-save-draft" disabled class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:shadow-none">Lưu phiếu xuất nháp</button>
        </div>
    </form>
</div>

<template id="issue-row-template">
    <div class="issue-item-row grid grid-cols-12 items-start gap-3 rounded-xl border border-slate-200 p-3">
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
            <input type="text" inputmode="decimal" data-field="quantity" required placeholder="Số lượng" class="issue-quantity min-h-11 w-full rounded-xl border border-slate-300 px-3 text-right tabular-nums">
            <p class="issue-stock-warning mt-1 hidden text-[11px] font-semibold text-rose-600"></p>
        </div>
        <div class="col-span-2">
            <input type="text" inputmode="decimal" data-field="unit_price" required value="0" placeholder="Đơn giá" class="issue-unit-price min-h-11 w-full rounded-xl border border-slate-300 px-3 text-right tabular-nums">
            <div class="mt-1 flex min-h-5 items-center justify-between gap-2"><p class="issue-price-source truncate text-[11px] text-slate-500">Chưa có giá · có thể nhập tay</p><button type="button" class="issue-reset-price shrink-0 text-[11px] font-semibold text-indigo-700 hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline" disabled>Đặt lại giá gốc</button></div>
        </div>
        <div class="col-span-2 flex min-h-11 items-center justify-end pr-2">
            <span class="issue-line-total text-sm font-semibold text-slate-800">0 đ</span>
        </div>
        <div class="col-span-1 flex min-h-11 items-center justify-end">
            <button type="button" class="remove-issue-row text-sm font-semibold text-rose-700">Xóa</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const balances = @json($balanceOptions);
    const priceCandidates = @json($issueSalePrices);
    @php
        $priceListOptions = $customerPriceLists->map(function ($list) {
            return [
                'id'=>$list->id,'code'=>$list->code,'name'=>$list->name,'type'=>$list->type,'manager_user_id'=>$list->manager_user_id,
                'global_user_ids'=>$list->globalUsers->pluck('id')->map(fn($id)=>(int)$id)->values(),'partner_id'=>$list->partner_id,'effective_from'=>$list->effective_from?->format('Y-m-d'),
                'effective_to'=>$list->effective_to?->format('Y-m-d'),'priority'=>$list->priority,
            ];
        })->values();
        $partnerOptions = $partners->map(function ($partner) {
            return ['id'=>$partner->id,'name'=>$partner->name,'tax_code'=>$partner->tax_code];
        })->values();
    @endphp
    const priceLists = @json($priceListOptions);
    const partners = @json($partnerOptions);
    const recipientSelect = document.querySelector('[name="recipient_partner_id"]');
    const recipientName = document.getElementById('issue-recipient-name');
    const issueDate = document.querySelector('[name="issue_date"]');
    const managerSelect = document.getElementById('issue-price-manager');
    const priceListSelect = document.getElementById('issue-price-list');
    const priceListHint = document.getElementById('issue-price-list-hint');
    const recipientPicker = document.getElementById('issue-recipient-picker');
    const recipientCard = document.getElementById('issue-recipient-card');
    const recipientCardName = document.getElementById('issue-recipient-card-name');
    const recipientCardMeta = document.getElementById('issue-recipient-card-meta');
    const recipientChange = document.getElementById('issue-recipient-change');
    const priceBadge = document.getElementById('issue-price-badge');
    const itemCount = document.getElementById('issue-item-count');
    const footerCount = document.getElementById('issue-footer-count');
    const footerQuantity = document.getElementById('issue-footer-quantity');
    const grandTotal = document.getElementById('issue-grand-total');
    const saveDraft = document.getElementById('issue-save-draft');
    const container = document.getElementById('issue-items');
    const template = document.getElementById('issue-row-template');

    function parseViNumber(value) {
        const raw=String(value ?? '').trim().replace(/\s/g,'');
        if(!raw) return 0;
        if(raw.includes(',')) return Number(raw.replace(/\./g,'').replace(',','.')) || 0;
        const dots=(raw.match(/\./g)||[]).length;
        if(dots>1 || (dots===1 && /\.\d{3}$/.test(raw))) return Number(raw.replace(/\./g,'')) || 0;
        return Number(raw) || 0;
    }

    function formatViNumber(value,maximumFractionDigits=3) {
        return new Intl.NumberFormat('vi-VN',{maximumFractionDigits}).format(Number(value)||0);
    }

    function normalizeNumericInput(input,maximumFractionDigits=3) {
        const value=parseViNumber(input.value);
        input.value=formatViNumber(value,maximumFractionDigits);
        return value;
    }

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
            ['global','customer'].includes(candidate.price_list_type) &&
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
            ((list.type==='customer' && String(list.manager_user_id)===String(managerId)) ||
                (list.type==='global' && (list.global_user_ids.length===0 || list.global_user_ids.map(String).includes(String(managerId))))) &&
            priceListIsEffective(list,date)
        );
        priceListSelect.innerHTML='<option value="">Chọn bảng giá áp dụng</option>';
        lists.forEach((list)=>{
            const option=document.createElement('option');
            option.value=list.id;
            option.textContent=`${list.code} — ${list.name} · ${list.type==='global' ? 'GLOBAL' : (list.partner_id ? 'CUSTOMER riêng' : 'CUSTOMER')}`;
            priceListSelect.appendChild(option);
        });
        priceListSelect.disabled=!managerId || lists.length===0;
        if(lists.some((list)=>String(list.id)===String(current))) priceListSelect.value=current;
        priceListHint.textContent=lists.length
            ? `${lists.length} bảng giá phù hợp`
            : (managerId ? 'Không có bảng giá được phân cho User và còn hiệu lực tại ngày xuất.' : 'Chọn người phụ trách để tải bảng giá.');
        updateContextSummary();
        refreshRowsForPriceList();
    }

    function medicineIdsForSelectedPriceList() {
        const date=issueDate?.value || new Date().toISOString().slice(0,10);
        return [...new Set(priceCandidates.filter((candidate)=>
            String(candidate.price_list_id)===String(priceListSelect.value) &&
            ['global','customer'].includes(candidate.price_list_type) && isEffective(candidate,date)
        ).map((candidate)=>String(candidate.medicine_id)))];
    }

    function applyPriceListToRow(row,preserveSelection=true) {
        const allowed=medicineIdsForSelectedPriceList();
        const tom=row._medicineTom;
        if(!tom) return;
        const current=preserveSelection ? tom.getValue() : '';
        tom.clear(true);
        tom.clearOptions();
        @foreach($issueMedicines as $medicine)
        if(allowed.includes('{{ $medicine->id }}')) tom.addOption({value:'{{ $medicine->id }}',text:@json($medicine->medicine_code.' — '.$medicine->name)});
        @endforeach
        tom.refreshOptions(false);
        tom.disable();
        if(priceListSelect.value){
            tom.enable();
            if(current && allowed.includes(String(current))) tom.setValue(current,true);
        }
        if(!current || !allowed.includes(String(current))) fillLots(row,tom.getValue());
        refreshPrice(row);
    }

    function updateSaveDraftState() {
        const hasRecipient=Boolean(recipientSelect?.value);
        const hasProduct=[...container.querySelectorAll('.issue-medicine-select')].some((select)=>Boolean(select.value));
        saveDraft.disabled=!(hasRecipient && hasProduct);
        saveDraft.title=saveDraft.disabled ? 'Chọn khách hàng và ít nhất một sản phẩm trước khi lưu nháp.' : '';
    }

    function updateRecipientCard() {
        const partner=partners.find((item)=>String(item.id)===String(recipientSelect?.value || ''));
        const selected=Boolean(partner);
        recipientPicker.classList.toggle('hidden',selected);
        recipientCard.classList.toggle('hidden',!selected);
        recipientChange.classList.toggle('hidden',!selected);
        recipientCardName.textContent=partner?.name || '';
        recipientCardMeta.textContent=partner?.tax_code ? `MST: ${partner.tax_code}` : 'Khách hàng đã chọn';
        updateSaveDraftState();
    }

    function updateContextSummary() {
        const list=priceLists.find((item)=>String(item.id)===String(priceListSelect.value));
        priceBadge.classList.toggle('hidden',!list);
        priceBadge.textContent=list ? (list.type==='global' ? 'Bảng giá chung' : 'Bảng giá khách hàng') : '';
        updateRecipientCard();
    }

    function refreshRowsForPriceList() {
        container.querySelectorAll('.issue-item-row').forEach((row)=>applyPriceListToRow(row,true));
    }

    function refreshPrice(row) {
        const medicineId=row.querySelector('.issue-medicine-select')?.value;
        const input=row.querySelector('.issue-unit-price');
        const source=row.querySelector('.issue-price-source');
        const candidate=resolveSalePrice(medicineId);
        const price=candidate?.company_sale_price === null || candidate?.company_sale_price === undefined ? 0 : Number(candidate.company_sale_price);
        const originalPrice=Number.isFinite(price) ? price : 0;
        row.dataset.originalPrice=String(originalPrice);
        input.value=formatViNumber(originalPrice,2);
        source.textContent=candidate
            ? `Giá bảng: ${formatViNumber(originalPrice,2)} đ · ${candidate.price_list_code || candidate.price_list_name || 'Bảng giá'}`
            : 'Chưa có giá bảng · có thể nhập tay';
        updateResetPriceState(row);
        updateLineTotal(row);
    }

    function updateResetPriceState(row) {
        const input=row.querySelector('.issue-unit-price');
        const reset=row.querySelector('.issue-reset-price');
        const original=Number(row.dataset.originalPrice || 0);
        const changed=Math.abs(parseViNumber(input?.value)-original)>0.000001;
        if(reset){ reset.disabled=!changed; reset.setAttribute('aria-disabled',String(!changed)); }
    }

    function updateOrderSummary() {
        const rows=[...container.querySelectorAll('.issue-item-row')];
        const activeRows=rows.filter((row)=>row.querySelector('.issue-medicine-select')?.value);
        const quantity=rows.reduce((sum,row)=>sum+parseViNumber(row.querySelector('.issue-quantity')?.value),0);
        const total=rows.reduce((sum,row)=>sum+(parseViNumber(row.querySelector('.issue-quantity')?.value)*parseViNumber(row.querySelector('.issue-unit-price')?.value)),0);
        const label=`${activeRows.length} sản phẩm`;
        itemCount.textContent=label; footerCount.textContent=label;
        footerQuantity.textContent=`· Tổng SL ${new Intl.NumberFormat('vi-VN',{maximumFractionDigits:3}).format(quantity)}`;
        grandTotal.textContent=new Intl.NumberFormat('vi-VN',{maximumFractionDigits:0}).format(total)+' đ';
        updateSaveDraftState();
    }

    function updateLineTotal(row) {
        const quantity=parseViNumber(row.querySelector('.issue-quantity')?.value);
        const price=parseViNumber(row.querySelector('.issue-unit-price')?.value);
        row.querySelector('.issue-line-total').textContent=new Intl.NumberFormat('vi-VN',{maximumFractionDigits:0}).format(quantity*price)+' đ';
        updateStockWarning(row); updateOrderSummary();
    }

    function updateStockWarning(row) {
        const quantityInput=row.querySelector('.issue-quantity');
        const lotSelect=row.querySelector('.issue-balance-select');
        const warning=row.querySelector('.issue-stock-warning');
        const balance=balances.find((item)=>String(item.id)===String(lotSelect?.value || ''));
        const quantity=parseViNumber(quantityInput?.value);
        const available=Number(balance?.quantity || 0);
        const after=available-quantity;
        const isNegative=Boolean(balance) && quantity>available;
        quantityInput.classList.toggle('border-rose-500',isNegative);
        quantityInput.classList.toggle('bg-rose-50',isNegative);
        quantityInput.classList.toggle('text-rose-700',isNegative);
        warning.classList.toggle('hidden',!isNegative);
        warning.textContent=isNegative
            ? `Vượt tồn ${new Intl.NumberFormat('vi-VN',{maximumFractionDigits:3}).format(quantity-available)} · tồn sau xuất ${new Intl.NumberFormat('vi-VN',{maximumFractionDigits:3}).format(after)}`
            : '';
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
        if(medicineId && matching.length>0){ lotSelect.value=String(matching[0].id); updateStockWarning(row); }
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
        if(priceListSelect.value) applyPriceListToRow(row,false);
        medicineSelect.addEventListener('change',(event)=>{ fillLots(row,event.target.value); refreshPrice(row); });
        const quantityInput=row.querySelector('.issue-quantity');
        const priceInput=row.querySelector('.issue-unit-price');
        quantityInput.addEventListener('input',()=>updateLineTotal(row));
        quantityInput.addEventListener('blur',()=>{ normalizeNumericInput(quantityInput,3); updateLineTotal(row); });
        row.querySelector('.issue-balance-select').addEventListener('change',()=>updateStockWarning(row));
        priceInput.addEventListener('input',()=>{ updateResetPriceState(row); updateLineTotal(row); });
        priceInput.addEventListener('blur',()=>{ normalizeNumericInput(priceInput,2); updateResetPriceState(row); updateLineTotal(row); });
        row.querySelector('.issue-reset-price').addEventListener('click',()=>{
            priceInput.value=formatViNumber(Number(row.dataset.originalPrice || 0),2);
            updateResetPriceState(row); updateLineTotal(row); priceInput.focus();
        });
        renumberRows();
    }

    document.querySelector('form')?.addEventListener('submit',()=>{
        container.querySelectorAll('.issue-item-row').forEach((row)=>{
            const quantity=row.querySelector('.issue-quantity');
            const price=row.querySelector('.issue-unit-price');
            if(quantity) quantity.value=String(parseViNumber(quantity.value));
            if(price) price.value=String(parseViNumber(price.value));
        });
    });
    document.getElementById('add-issue-row').addEventListener('click',addRow);
    recipientSelect?.addEventListener('change',()=>{
        const partner=partners.find((item)=>String(item.id)===String(recipientSelect.value));
        recipientName.value=partner?.name || '';
        updateContextSummary();
    });
    managerSelect.addEventListener('change',refreshPriceLists);
    priceListSelect.addEventListener('change',()=>{
        const list=priceLists.find((item)=>String(item.id)===String(priceListSelect.value));
        if(list?.type==='customer' && list.partner_id && recipientSelect?.tomselect) recipientSelect.tomselect.setValue(String(list.partner_id));
        updateContextSummary(); refreshRowsForPriceList();
    });
    issueDate?.addEventListener('change',refreshPriceLists);
    container.addEventListener('click',(event)=>{
        const button=event.target.closest('.remove-issue-row');
        if(!button || container.querySelectorAll('.issue-item-row').length===1) return;
        const row=button.closest('.issue-item-row');
        const select=row.querySelector('.issue-medicine-select');
        if(select?.tomselect) select.tomselect.destroy();
        row.remove(); renumberRows(); updateOrderSummary();
    });
    addRow();
});
</script>
@endsection
