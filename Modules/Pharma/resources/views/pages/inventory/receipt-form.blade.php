@extends('Admin::layouts.master')
@section('title','Lập phiếu nhập kho')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
        <a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="ml-4 text-sm font-semibold text-slate-600">Danh sách phiếu nhập</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">Lập phiếu nhập kho</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $warehouse->name }} · Phiếu mới được lưu ở trạng thái DRAFT.</p>
    </header>

    <form method="POST" action="{{ route('admin.pharma.inventory.receipts.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm font-medium">Ngày nhập
                <input type="date" name="receipt_date" value="{{ old('receipt_date',now()->toDateString()) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
            <label class="text-sm font-medium">Nhà cung cấp *
                <x-select-search id="receipt-supplier" name="supplier_name" placeholder="Tìm nhà cung cấp..." required>
                    <option value="">Chọn nhà cung cấp</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->name }}" @selected(old('supplier_name') === $partner->name)>
                            {{ $partner->name }}{{ $partner->tax_code ? ' — MST '.$partner->tax_code : '' }}
                        </option>
                    @endforeach
                </x-select-search>
            </label>
            <label class="text-sm font-medium">Số hóa đơn
                <input name="invoice_number" value="{{ old('invoice_number') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
            <label class="text-sm font-medium">Ngày hóa đơn
                <input type="date" name="invoice_date" value="{{ old('invoice_date') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
            </label>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Chi tiết thuốc / lô</h2>
                    <p class="mt-1 text-xs text-slate-500">Tìm theo mã hoặc tên thuốc trong Medicine Master.</p>
                </div>
                <button type="button" id="add-receipt-row" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">+ Thêm dòng</button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <div class="min-w-[1000px]">
                    <div class="grid grid-cols-12 gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <div class="col-span-4">Tên thuốc / Mã thuốc</div>
                        <div class="col-span-2">Số lô</div>
                        <div class="col-span-2">Hạn dùng</div>
                        <div class="col-span-1">Số lượng</div>
                        <div class="col-span-2">Giá nhập chưa VAT</div>
                        <div class="col-span-1 text-right">Thao tác</div>
                    </div>
                    <div id="receipt-items" class="space-y-3 pt-3"></div>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <div class="flex justify-end">
            <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Lưu phiếu nhập nháp</button>
        </div>
    </form>
</div>

<template id="receipt-row-template">
    <div class="receipt-item-row grid grid-cols-12 gap-3 rounded-xl border border-slate-200 p-3">
        <div class="col-span-4">
            <label class="mb-1 block text-xs font-medium text-slate-500 lg:hidden">Tên thuốc / Mã thuốc</label>
            <select data-field="medicine_id" required class="receipt-medicine-select min-h-11 w-full rounded-xl border border-slate-300 px-3">
                <option value="">Chọn thuốc</option>
                @foreach($medicines as $m)
                    <option value="{{ $m->id }}">{{ $m->medicine_code }} — {{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500 lg:hidden">Số lô</label>
            <input data-field="batch_number" required placeholder="Số lô" class="min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </div>
        <div class="col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500 lg:hidden">Hạn dùng</label>
            <input type="date" data-field="expiry_date" required class="min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </div>
        <div class="col-span-1">
            <label class="mb-1 block text-xs font-medium text-slate-500 lg:hidden">Số lượng</label>
            <input type="number" step="0.001" min="0.001" data-field="quantity" required placeholder="SL" class="min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </div>
        <div class="col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500 lg:hidden">Giá nhập chưa VAT</label>
            <input type="number" step="0.0001" min="0" data-field="unit_price_ex_vat" required placeholder="Giá nhập" class="min-h-11 w-full rounded-xl border border-slate-300 px-3">
            <input type="hidden" data-field="vat_rate" value="0">
        </div>
        <div class="col-span-1 flex items-center justify-end">
            <button type="button" class="remove-receipt-row text-sm font-semibold text-rose-700">Xóa</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('receipt-items');
    const template = document.getElementById('receipt-row-template');
    const addButton = document.getElementById('add-receipt-row');
    let nextIndex = 0;

    function renumberRows() {
        container.querySelectorAll('.receipt-item-row').forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach((field) => {
                field.name = `items[${index}][${field.dataset.field}]`;
            });
        });
    }

    function enhanceMedicineSelect(select) {
        new TomSelect(select, {
            plugins: ['dropdown_input'],
            placeholder: 'Tìm mã hoặc tên thuốc...',
            create: false,
            allowEmptyOption: true,
            dropdownParent: 'body'
        });
    }

    function addRow() {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.receipt-item-row');
        row.dataset.rowKey = nextIndex++;
        container.appendChild(fragment);
        enhanceMedicineSelect(row.querySelector('.receipt-medicine-select'));
        renumberRows();
    }

    addButton.addEventListener('click', addRow);
    container.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-receipt-row');
        if (!button) return;
        if (container.querySelectorAll('.receipt-item-row').length === 1) return;
        const row = button.closest('.receipt-item-row');
        const select = row.querySelector('.receipt-medicine-select');
        if (select?.tomselect) select.tomselect.destroy();
        row.remove();
        renumberRows();
    });

    addRow();
});
</script>
@endsection
