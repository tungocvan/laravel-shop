@extends('ClientPortal::layouts.application')
@section('title', 'Tồn đầu kỳ')
@section('content')
<div class="mx-auto w-full max-w-6xl space-y-5 px-4 py-6">
    <header class="border-b border-slate-200 pb-4">
        <a href="{{ route('client.pharma.inventory') }}" class="text-sm font-semibold text-indigo-700">← Tồn kho</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-wide text-indigo-600">Khởi tạo sổ kho</p>
        <h1 class="mt-1 text-2xl font-extrabold text-slate-950">Tồn đầu kỳ</h1>
        <p class="mt-1 text-sm text-slate-600">Chỉ ghi nhận lô tồn tại tại thời điểm bắt đầu quản lý kho. Các phát sinh sau đó phải đi qua phiếu nhập/xuất.</p>
    </header>
    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-bold">Không thể ghi nhận tồn đầu kỳ</p>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:items-start">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-950">Ghi nhận thủ công</h2>
            <p class="mt-1 text-xs text-slate-500">Thêm một lô tồn đầu kỳ.</p>
            <form method="POST" action="{{ route('client.pharma.inventory.opening.manual') }}" class="mt-5 space-y-4" onsubmit="return confirm('Xác nhận ghi nhận tồn đầu kỳ cho lô này?');">
                @csrf
                <label class="block text-sm font-semibold text-slate-700">Thuốc <span class="text-rose-600">*</span>
                    <select name="medicine_id" id="opening-medicine" required class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                        <option value="">Chọn thuốc</option>
                        @foreach($medicines as $medicine)
                            <option value="{{ $medicine->id }}" @selected(old('medicine_id') == $medicine->id)>{{ $medicine->medicine_code }} · {{ $medicine->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="block text-sm font-semibold text-slate-700">Số lô <span class="text-rose-600">*</span>
                        <input name="batch_number" value="{{ old('batch_number') }}" required maxlength="100" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Hạn dùng <span class="text-rose-600">*</span>
                        <x-pwa-date name="expiry_date" :value="old('expiry_date')" required class="min-h-11" />
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Tồn đầu kỳ <span class="text-rose-600">*</span>
                        <input type="number" name="opening_quantity" value="{{ old('opening_quantity') }}" required min="0.000001" step="any" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block text-sm font-semibold text-slate-700">Giá nhập / Giá vốn (đ)
                        <input type="number" name="manual_cost_price" id="opening-cost" value="{{ old('manual_cost_price') }}" min="0.01" step="0.01" inputmode="decimal" placeholder="Tự lấy giá NCC nếu có" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                        <span id="opening-cost-hint" class="mt-1 block text-xs font-normal text-slate-500">Chọn thuốc để lấy giá vốn tham chiếu NCC. Có thể chỉnh trước khi lưu.</span>
                    </label>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold text-slate-600">Giá trị tồn đầu kỳ (ước tính)</p>
                        <p id="opening-value" class="mt-2 text-lg font-extrabold text-slate-900">—</p>
                        <p class="mt-1 text-xs text-slate-500">Số lượng × giá vốn, làm tròn đến đồng.</p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white hover:bg-indigo-700">Ghi nhận tồn đầu kỳ</button>
                </div>
            </form>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-950">Import hàng loạt</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Dùng file Excel khi khởi tạo nhiều lô cùng lúc. Mã thuốc phải tồn tại trong Medicine Master.</p>
            <a href="{{ route('client.pharma.inventory.opening.template') }}" class="mt-4 flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">↓ Tải file mẫu Excel</a>
            <form method="POST" action="{{ route('client.pharma.inventory.opening.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3" onsubmit="return confirm('Xác nhận import và ghi sổ tồn đầu kỳ? Các lô đã có số dư sẽ không được ghi đè.');">
                @csrf
                <label class="block text-sm font-semibold text-slate-700">Chọn file XLSX/CSV (tối đa 10 MB)
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-2 block w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
                </label>
                <p class="text-xs leading-5 text-slate-500">Cột bắt buộc: Ma thuoc, So lo, Han dung (YYYY-MM-DD), Ton dau ky. Tối đa 1.000 dòng.</p>
                <button type="submit" class="min-h-11 w-full rounded-xl bg-slate-950 px-4 text-sm font-bold text-white hover:bg-slate-800">Import tồn đầu kỳ</button>
            </form>
        </section>
    </div>
    <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Lưu ý: Chỉ khởi tạo lô chưa có số dư hoặc giao dịch kho. Không ghi đè lô đã phát sinh tồn; hãy kiểm tra dữ liệu trước khi lưu.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const prices = @json($supplierCosts);
    const medicine = document.getElementById('opening-medicine');
    const cost = document.getElementById('opening-cost');
    const quantity = document.querySelector('input[name="opening_quantity"]');
    const hint = document.getElementById('opening-cost-hint');
    const value = document.getElementById('opening-value');
    const format = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
    function updateValue() {
        const q = Number(quantity.value);
        const p = Number(cost.value);
        value.textContent = quantity.value !== '' && cost.value !== '' && q > 0 && p > 0
            ? format.format(Math.round(q * p)) + ' đ' : '—';
    }
    function updateCost() {
        const price = Number(prices[medicine.value] || 0);
        cost.value = price > 0 ? String(Math.round(price * 100) / 100) : '';
        hint.textContent = price > 0
            ? 'Giá vốn NCC tham chiếu: ' + format.format(price) + ' đ. Có thể chỉnh riêng cho lô này.'
            : 'Chưa có giá NCC hợp lệ. Có thể nhập giá vốn thủ công.';
        updateValue();
    }
    medicine.addEventListener('change', updateCost);
    cost.addEventListener('input', updateValue);
    quantity.addEventListener('input', updateValue);
    if (medicine.value && cost.value === '') updateCost();
    else updateValue();
});
</script>
@endsection
