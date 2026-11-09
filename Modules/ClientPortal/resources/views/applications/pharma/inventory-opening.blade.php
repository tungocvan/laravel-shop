@extends('ClientPortal::layouts.application')
@section('title', 'Tồn đầu kỳ')
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto w-full max-w-7xl space-y-4 px-4 py-5">
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
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-950">Danh sách lô tồn đầu kỳ</h2>
                <p class="mt-1 text-xs text-slate-500">Số lượng gốc tại thời điểm mở sổ, không phải tồn hiện tại.</p>
            </div>
            <a href="{{ route('client.pharma.inventory.opening.export') }}" data-pwa-file-handoff class="inline-flex min-h-11 items-center rounded-xl border border-indigo-200 px-4 text-sm font-bold text-indigo-700">↓ Export Excel để import</a>
        </div>
        <form method="GET" action="{{ route('client.pharma.inventory.opening') }}" id="opening-search-form" class="mt-4 flex flex-wrap items-end gap-2">
            <div class="min-w-[14rem] flex-1">
                <x-pwa-search name="search" :value="$search" label="Tìm kiếm lô đầu kỳ" placeholder="Tên thuốc, mã thuốc, số lô..." />
            </div>
            
            @if($search !== '')
                <a href="{{ route('client.pharma.inventory.opening') }}" class="inline-flex min-h-11 items-center px-3 text-sm font-semibold text-rose-700">Xóa lọc</a>
            @endif
        </form>
        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[820px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-700"><tr>
                    <th class="px-3 py-3">Mã thuốc / Tên thuốc</th>
                    <th class="px-3 py-3">Số lô</th>
                    <th class="px-3 py-3">Hạn dùng</th>
                    <th class="px-3 py-3 text-right">Tồn đầu kỳ</th><th class="px-3 py-3 text-right">Đơn giá (đ)</th><th class="px-3 py-3 text-right">Thao tác</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($openingLots as $lot)
                        <tr>
                            <td class="px-3 py-3"><span class="font-bold">{{ $lot->medicine?->medicine_code }}</span><span class="mt-1 block text-xs text-slate-600">{{ $lot->medicine?->name }}</span></td>
                            <td class="px-3 py-3">{{ $lot->batch_number }}</td>
                            <td class="px-3 py-3">{{ $lot->expiry_date?->format('Y-m-d') }}</td>
                            <td class="px-3 py-3 text-right font-bold">{{ number_format((float) $lot->opening_quantity, 2, ',', '.') }}</td><td class="px-3 py-3 text-right">{{ $lot->manual_cost_price === null ? "—" : number_format((float) $lot->manual_cost_price, 2, ',', '.') }}</td><td class="px-3 py-3 text-right"><button type="button" data-opening-edit data-action="{{ route('client.pharma.inventory.opening.update', $lot->id) }}" data-lot="{{ $lot->batch_number }}" data-expiry="{{ $lot->expiry_date?->format('Y-m-d') }}" data-quantity="{{ $lot->opening_quantity }}" data-price="{{ $lot->manual_cost_price }}" class="min-h-10 rounded-xl border border-indigo-200 px-3 text-xs font-bold text-indigo-700">Sửa</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Chưa có lô tồn đầu kỳ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $openingLots->links() }}</div>
        <p class="mt-3 text-xs text-amber-800">File export giữ đúng định dạng import, nhưng các lô đã ghi sổ sẽ bị từ chối khi import lại để tránh ghi đè hoặc cộng trùng. Chỉ import các lô mới chưa phát sinh.</p>
    </section>
    <div id="opening-actions" class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:items-start">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-slate-950">Thêm lô tồn đầu kỳ</h2>
            <p class="mt-1 text-xs text-slate-500">Thêm một lô tồn đầu kỳ.</p>
            <form method="POST" action="{{ route('client.pharma.inventory.opening.manual') }}" class="mt-5 space-y-4" onsubmit="return confirm('Xác nhận ghi nhận tồn đầu kỳ cho lô này?');">
                @csrf
                <div>
                    <label for="opening-medicine" class="mb-1 block text-sm font-semibold text-slate-700">Thuốc <span class="text-rose-600">*</span></label>
                    <x-pwa-select-search id="opening-medicine" name="medicine_id" placeholder="Chọn thuốc" search-placeholder="Tìm mã hoặc tên thuốc..." :selected="old('medicine_id', '')">
                        @foreach($medicines as $medicine)
                            <button type="button" role="option" data-pwa-select-search-option data-value="{{ $medicine->id }}" data-label="{{ $medicine->medicine_code }} · {{ $medicine->name }}" data-search="{{ $medicine->medicine_code }} {{ $medicine->name }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-800 hover:bg-indigo-50">{{ $medicine->medicine_code }} · {{ $medicine->name }}</button>
                        @endforeach
                    </x-pwa-select-search>
                </div>
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
    <div id="opening-edit-dialog" class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-950/60 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="opening-edit-title">
        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
            <div class="flex items-center justify-between gap-3">
                <h2 id="opening-edit-title" class="text-lg font-extrabold text-slate-950">Chỉnh sửa lô đầu kỳ</h2>
                <button type="button" data-opening-close class="min-h-11 rounded-xl px-3 text-sm font-bold text-slate-600" aria-label="Đóng">✕</button>
            </div>
            <p class="mt-2 text-xs text-amber-800">Chỉ được sửa khi lô chưa có phát sinh nhập/xuất. Sổ kho sẽ được cập nhật đồng bộ.</p>
            <form id="opening-edit-form" method="POST" class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return confirm('Xác nhận hiệu chỉnh tồn đầu kỳ và sổ kho?');">
                @csrf
                @method('PUT')
                <label class="text-sm font-semibold text-slate-700">Số lô<input name="batch_number" required maxlength="100" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-semibold text-slate-700">Hạn dùng<input type="date" name="expiry_date" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-semibold text-slate-700">Tồn đầu kỳ<input type="number" name="opening_quantity" min="0.001" step="0.001" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-semibold text-slate-700">Đơn giá vốn (đ)<input type="number" name="manual_cost_price" min="0.01" step="0.01" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" data-opening-close class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold">Hủy</button>
                    <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
    <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Lưu ý: Chỉ khởi tạo lô chưa có số dư hoặc giao dịch kho. Không ghi đè lô đã phát sinh tồn; hãy kiểm tra dữ liệu trước khi lưu.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('opening-search-form');
    const search = form?.querySelector('input[name="search"]');
    let debounce;
    search?.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            if (search.value.trim() !== new URLSearchParams(location.search).get('search')?.trim()) {
                form.requestSubmit();
            }
        }, 350);
    });
    const dialog = document.getElementById('opening-edit-dialog');
    const editForm = document.getElementById('opening-edit-form');
    const close = () => { dialog.classList.add('hidden'); dialog.classList.remove('flex'); };
    document.querySelectorAll('[data-opening-edit]').forEach(button => button.addEventListener('click', () => {
        editForm.action = button.dataset.action;
        for (const [field, value] of Object.entries({batch_number:button.dataset.lot, expiry_date:button.dataset.expiry, opening_quantity:button.dataset.quantity, manual_cost_price:button.dataset.price})) {
            editForm.elements.namedItem(field).value = value || '';
        }
        dialog.classList.remove('hidden'); dialog.classList.add('flex');
        editForm.elements.namedItem('batch_number').focus();
    }));
    dialog.querySelectorAll('[data-opening-close]').forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
});
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
