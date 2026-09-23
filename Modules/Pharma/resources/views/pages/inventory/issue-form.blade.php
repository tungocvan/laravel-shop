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
@endphp
<div class="mx-auto max-w-7xl space-y-6">
    <header>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
        <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="ml-4 text-sm font-semibold text-slate-600">Danh sách phiếu xuất</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">Lập phiếu xuất kho</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $warehouse->name }} · Chọn thuốc trước, sau đó chọn lô còn tồn theo hạn dùng gần nhất (FEFO).</p>
    </header>

    <form method="POST" action="{{ route('admin.pharma.inventory.issues.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2">
            <label class="text-sm font-medium">Ngày xuất
                <input type="date" name="issue_date" value="{{ old('issue_date',now()->toDateString()) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
            <label class="text-sm font-medium">Khách hàng / nơi nhận
                <x-select-search id="issue-recipient" name="recipient_name" placeholder="Tìm khách hàng / nơi nhận...">
                    <option value="">Chọn khách hàng</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->name }}" @selected(old('recipient_name')===$partner->name)>{{ $partner->name }}{{ $partner->tax_code ? ' · MST '.$partner->tax_code : '' }}</option>
                    @endforeach
                </x-select-search>
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
                <div class="min-w-[950px]">
                    <div class="grid grid-cols-12 gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <div class="col-span-4">Tên thuốc / Mã thuốc</div>
                        <div class="col-span-4">Số lô · Hạn dùng · Tồn khả dụng</div>
                        <div class="col-span-2">Số lượng xuất</div>
                        <div class="col-span-2 text-right">Thao tác</div>
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
        <div class="col-span-4">
            <select class="issue-medicine-select w-full" required>
                <option value="">Chọn thuốc</option>
                @foreach($issueMedicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_code }} — {{ $medicine->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-span-4">
            <select data-field="balance_id" class="issue-balance-select min-h-11 w-full rounded-xl border border-slate-300 px-3" required disabled>
                <option value="">Chọn thuốc trước</option>
            </select>
        </div>
        <div class="col-span-2">
            <input type="number" step="0.001" min="0.001" data-field="quantity" required placeholder="Số lượng xuất" class="min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </div>
        <div class="col-span-2 flex items-center justify-end">
            <button type="button" class="remove-issue-row text-sm font-semibold text-rose-700">Xóa</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const balances = @json($balanceOptions);
    const container = document.getElementById('issue-items');
    const template = document.getElementById('issue-row-template');

    function renumberRows() {
        container.querySelectorAll('.issue-item-row').forEach((row,index) => {
            row.querySelectorAll('[data-field]').forEach((field) => field.name = `items[${index}][${field.dataset.field}]`);
        });
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
            onChange:(value)=>fillLots(row,value)
        });
        medicineSelect.addEventListener('change',(event)=>fillLots(row,event.target.value));
        renumberRows();
    }

    document.getElementById('add-issue-row').addEventListener('click',addRow);
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
