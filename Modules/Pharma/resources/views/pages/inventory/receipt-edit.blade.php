@extends('Admin::layouts.master')
@section('title','Cập nhật phiếu nhập')
@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <header><a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu nhập</a><h1 class="mt-2 text-2xl font-bold">{{ $receipt->status === 'draft' ? 'Sửa phiếu nhập nháp' : 'Cập nhật thông tin phiếu nhập' }}</h1>
    @if($receipt->status === 'posted')<p class="mt-1 text-sm text-amber-700">Phiếu đã ghi sổ: chỉ cập nhật thông tin chứng từ, không thay đổi thuốc/lô/HSD/số lượng/giá nhập.</p>@endif</header>
    <form method="POST" action="{{ route('admin.pharma.inventory.receipts.update',$receipt) }}" class="space-y-5">@csrf @method('PUT')
        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm font-medium">Ngày nhập<input type="date" name="receipt_date" required value="{{ old('receipt_date',$receipt->receipt_date->toDateString()) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm font-medium">Nhà cung cấp *
                <x-select-search id="receipt-edit-supplier" name="supplier_name" placeholder="Tìm nhà cung cấp..."><option value="">Chọn nhà cung cấp</option>@foreach($partners as $partner)<option value="{{ $partner->name }}" @selected(old('supplier_name',$receipt->supplier_name)===$partner->name)>{{ $partner->name }}{{ $partner->tax_code ? ' — MST '.$partner->tax_code : '' }}</option>@endforeach</x-select-search>
            </label>
            <label class="text-sm font-medium">Số hóa đơn<input name="invoice_number" value="{{ old('invoice_number',$receipt->invoice_number) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm font-medium">Ngày hóa đơn<input type="date" name="invoice_date" value="{{ old('invoice_date',$receipt->invoice_date?->toDateString()) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
        </section>
        @if($receipt->status === 'draft')
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold">Chi tiết thuốc / lô</h2><p class="mt-1 text-xs text-slate-500">Phiếu nháp được phép chỉnh sửa chi tiết hàng hóa.</p>
                <div class="mt-4 overflow-x-auto"><table class="min-w-[850px] w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-2">Thuốc</th><th class="px-3 py-2">Số lô</th><th class="px-3 py-2">HSD</th><th class="px-3 py-2">SL</th><th class="px-3 py-2">Giá nhập chưa VAT</th></tr></thead>
                    <tbody>@foreach($receipt->items as $i=>$item)<tr>
                        <td class="px-3 py-2">{{ $item->medicine_id }}<input type="hidden" name="items[{{ $i }}][medicine_id]" value="{{ $item->medicine_id }}"></td>
                        <td class="px-3 py-2"><input name="items[{{ $i }}][batch_number]" value="{{ $item->batch_number }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
                        <td class="px-3 py-2"><input type="date" name="items[{{ $i }}][expiry_date]" value="{{ $item->expiry_date->toDateString() }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
                        <td class="px-3 py-2"><input type="number" step="0.001" min="0.001" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
                        <td class="px-3 py-2"><input type="number" step="0.0001" min="0" name="items[{{ $i }}][unit_price_ex_vat]" value="{{ $item->unit_price_ex_vat }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"><input type="hidden" name="items[{{ $i }}][vat_rate]" value="{{ $item->vat_rate }}"></td>
                    </tr>@endforeach</tbody>
                </table></div>
            </section>
        @endif
        <div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Lưu thay đổi</button></div>
    </form>
</div>
@endsection
