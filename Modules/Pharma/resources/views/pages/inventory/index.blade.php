@extends('Admin::layouts.master')
@section('title', 'Tồn kho Pharma')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.pharma.dashboard') }}" class="text-sm font-semibold text-indigo-700">← Quay về Pharma</a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma Inventory</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Tồn kho theo lô & hạn dùng</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $warehouse->name }} · Medicine Master là nguồn mã thuốc duy nhất.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pharma.inventory.movements.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">Xuất–Nhập–Tồn</a>
            <a href="{{ route('admin.pharma.inventory.export') }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800">Export tồn kho</a>
            @can('create_pharma')
                <a href="{{ route('admin.pharma.inventory.opening.create') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Tồn đầu kỳ</a>
                <a href="{{ route('admin.pharma.inventory.receipts.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">+ Phiếu nhập</a>
                <a href="{{ route('admin.pharma.inventory.issues.create') }}" class="rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700">+ Phiếu xuất</a>
            @endcan
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $errors->first() }}</div>
    @endif



    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Giá trị tồn theo giá vốn</p>
            <p class="mt-2 text-2xl font-bold text-emerald-950">{{ number_format($totalInventoryValue, 0, ',', '.') }} đ</p>
            <p class="mt-1 text-xs text-emerald-700">Ưu tiên giá vốn điều chỉnh theo lô; nếu chưa có sẽ dùng Supplier Tracking đang hiệu lực.</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Lô chưa định giá</p>
            <p class="mt-2 text-2xl font-bold text-amber-950">{{ number_format($unpricedBalanceCount) }}</p>
            <p class="mt-1 text-xs text-amber-700">Các lô còn tồn chưa có cả giá vốn điều chỉnh và giá vốn NCC đang hiệu lực.</p>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['expiry_warning' => 'lt6', 'page' => null]) }}" class="block rounded-2xl border border-orange-200 bg-orange-50 p-5 transition hover:border-orange-300 hover:shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Giá trị hàng cận hạn ≤ 6 tháng</p>
            <p class="mt-2 text-2xl font-bold text-orange-950">{{ number_format($nearExpiryInventoryValue, 0, ',', '.') }} đ</p>
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ number_format($nearExpiryBalanceCount) }} lô còn tồn · Không tính hàng đã hết hạn.</p>
        </a>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Hàng hết hạn còn tồn</p>
            <p class="mt-2 text-2xl font-bold text-rose-950">{{ number_format($expiredInventoryValue, 0, ',', '.') }} đ</p>
            <p class="mt-1 text-xs font-semibold text-rose-700">{{ number_format($expiredBalanceCount) }} lô còn tồn đã quá hạn dùng.</p>
        </div>
    </section>

    <form method="GET" id="inventory-filters"
          class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(220px,1fr)_auto_auto_auto_auto_auto_auto]">
        <input name="q" value="{{ request('q') }}" oninput="window.clearTimeout(this._inventorySearchTimer); this._inventorySearchTimer = window.setTimeout(() => this.form.submit(), 450)" placeholder="Tìm mã thuốc / tên thuốc" class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm">
        <select name="expiry_warning" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Tất cả cảnh báo</option>
            <option value="expired" @selected(request('expiry_warning') === 'expired')>Đã hết hạn</option>
            <option value="lt1" @selected(request('expiry_warning') === 'lt1')>Còn dưới 1 tháng</option>
            <option value="lt3" @selected(request('expiry_warning') === 'lt3')>Còn dưới 3 tháng</option>
            <option value="lt6" @selected(request('expiry_warning') === 'lt6')>Còn dưới 6 tháng</option>
            <option value="safe" @selected(request('expiry_warning') === 'safe')>Không cảnh báo (≥ 6 tháng)</option>
        </select>
        <select name="cost_status" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Tất cả giá vốn</option>
            <option value="priced" @selected(request('cost_status') === 'priced')>Đã có giá vốn</option>
            <option value="unpriced" @selected(request('cost_status') === 'unpriced')>Chưa có giá vốn</option>
        </select>
        <select name="value_sort" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Sắp xếp mặc định</option>
            <option value="value_desc" @selected(request('value_sort') === 'value_desc')>Giá trị tồn: lớn nhất</option>
            <option value="value_asc" @selected(request('value_sort') === 'value_asc')>Giá trị tồn: nhỏ nhất</option>
        </select>
        <select name="per_page" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm" aria-label="Số dòng mỗi trang">
            @foreach([25, 50, 100] as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / trang</option>
            @endforeach
        </select>
        <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm">
            <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()" @checked(request('in_stock'))> Chỉ còn tồn
        </label>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Xóa bộ lọc</a>
    </form>

    <div id="inventory-selection-bar" class="hidden items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3">
        <div class="text-sm font-semibold text-indigo-900">Đã chọn <span id="inventory-selected-count">0</span> dòng trên trang này</div>
        <div class="flex gap-2">
            <button type="button" onclick="clearInventorySelection()" class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700">Bỏ chọn</button>
            <button type="button" onclick="openInventoryExportModal()" class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Xuất Excel đã chọn</button>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3"><input id="inventory-select-all" type="checkbox" onchange="toggleInventoryPage(this.checked)" aria-label="Chọn tất cả dòng trên trang"></th>
                        <th class="px-4 py-3">Mã thuốc</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Số lô</th>
                        <th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn đầu</th>
                        <th class="px-4 py-3 text-right">Tồn cuối</th><th class="px-4 py-3 text-right">Giá vốn</th><th class="px-4 py-3 text-right">Giá trị tồn</th><th class="px-4 py-3">Cảnh báo</th><th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($balances as $row)
                        <tr>
                            <td class="px-4 py-4"><input class="inventory-row-checkbox" type="checkbox" value="{{ $row->id }}" onchange="syncInventorySelection()" aria-label="Chọn {{ $row->medicine->medicine_code }} {{ $row->batch_number }}"></td>
                            <td class="px-4 py-4 font-mono text-xs font-semibold text-indigo-700">{{ $row->medicine->medicine_code }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $row->medicine->name }}</div>
                                <div class="text-xs text-slate-500">{{ $row->medicine->unit ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-4 font-mono">{{ $row->batch_number }}</td>
                            <td class="px-4 py-4">{{ $row->expiry_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-4 text-right">{{ number_format((float) $row->opening_quantity, 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right font-bold">{{ number_format((float) $row->quantity_on_hand, 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right">
                                @if($row->average_cost_price !== null)
                                    <div class="font-semibold">{{ number_format($row->average_cost_price, 0, ',', '.') }} đ</div>
                                    @if($row->cost_source === 'manual')
                                        <div class="text-xs font-medium text-indigo-600">Điều chỉnh thủ công</div>
                                    @else
                                        <div class="text-xs text-slate-500">NCC · {{ $row->supplier_cost_count }} nguồn</div>
                                    @endif
                                @else
                                    <div class="text-xs font-semibold text-amber-700">Chưa có giá vốn</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right font-semibold">
                                @if($row->average_cost_price !== null)
                                    {{ number_format((float) $row->quantity_on_hand * $row->average_cost_price, 0, ',', '.') }} đ
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $today = now()->startOfDay();
                                    $expiry = $row->expiry_date->copy()->startOfDay();
                                    $daysLeft = $today->diffInDays($expiry, false);
                                    $monthsLeft = (int) floor($today->floatDiffInMonths($expiry, false));
                                @endphp
                                @if($daysLeft < 0)
                                    <span class="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Đã hết hạn</span>
                                @elseif($expiry->lt($today->copy()->addMonths(6)))
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                        Sắp hết hạn · {{ $monthsLeft < 1 ? 'còn '.max(0, (int) ceil($daysLeft)).' ngày' : 'còn '.$monthsLeft.' tháng' }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                @can('edit_pharma')
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="document.getElementById('edit-balance-{{ $row->id }}').showModal()" class="text-xs font-semibold text-indigo-700">Sửa</button>
                                        <button type="button" onclick="document.getElementById('delete-balance-{{ $row->id }}').showModal()" class="text-xs font-semibold text-rose-700">Xóa</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-12 text-center text-slate-500">Chưa có tồn kho. Hãy lập và ghi sổ phiếu nhập.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $balances->links() }}</div>
    </section>

    <dialog id="inventory-export-modal" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
        <form method="POST" action="{{ route('admin.pharma.inventory.export-selected') }}" class="rounded-2xl bg-white p-6" onsubmit="prepareInventoryExport(this)">
            @csrf
            <div id="inventory-export-ids"></div>
            <h3 class="text-lg font-bold text-slate-950">Xuất Excel các dòng đã chọn?</h3>
            <p class="mt-2 text-sm text-slate-600">File chỉ chứa các dòng đang được chọn trên trang hiện tại.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Xuất Excel</button>
            </div>
        </form>
    </dialog>

    @can('edit_pharma')
        @foreach($balances as $row)
            <dialog id="edit-balance-{{ $row->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                <form method="POST" action="{{ route('admin.pharma.inventory.balances.update', $row) }}" class="rounded-2xl bg-white p-6">
                    @csrf
                    @method('PUT')
                    <h3 class="text-lg font-bold text-slate-950">Sửa thông tin lô</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $row->medicine->medicine_code }} · {{ $row->medicine->name }}</p>
                    <div class="mt-5 space-y-4">
                        <label class="block text-sm font-semibold text-slate-700">Số lô
                            <input name="batch_number" value="{{ $row->batch_number }}" required maxlength="100" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Hạn dùng
                            <input type="date" name="expiry_date" value="{{ $row->expiry_date->format('Y-m-d') }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Nguồn giá vốn
                            <select name="cost_mode" onchange="const manual=this.value==='manual'; const box=this.closest('form').querySelector('[data-manual-cost]'); box.classList.toggle('hidden',!manual); box.querySelectorAll('input,textarea').forEach(el=>el.disabled=!manual)" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 font-normal">
                                <option value="supplier" @selected($row->manual_cost_price === null)>Tự động từ Supplier Tracking</option>
                                <option value="manual" @selected($row->manual_cost_price !== null)>Điều chỉnh thủ công theo lô</option>
                            </select>
                        </label>
                        <div data-manual-cost @class(['space-y-4','hidden'=>$row->manual_cost_price === null])>
                            <label class="block text-sm font-semibold text-slate-700">Giá vốn
                                <div class="mt-1 flex items-center gap-2"><input type="number" name="manual_cost_price" min="0" step="0.01" value="{{ $row->manual_cost_price !== null ? (float)$row->manual_cost_price : '' }}" @disabled($row->manual_cost_price === null) class="min-h-11 w-full rounded-xl border border-slate-300 px-3 font-normal"><span class="text-sm text-slate-500">đ</span></div>
                                <span class="mt-1 block text-xs font-normal text-slate-500">Nhập 0 để lưu đúng giá vốn 0 đ. Không có giá thủ công thì chọn nguồn tự động phía trên.</span>
                            </label>
                            <label class="block text-sm font-semibold text-slate-700">Lý do điều chỉnh
                                <textarea name="cost_adjustment_reason" maxlength="500" rows="2" @disabled($row->manual_cost_price === null) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-normal" placeholder="Ví dụ: điều chỉnh theo chứng từ / xác nhận kế toán"></textarea>
                            </label>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Không thay đổi Tồn đầu/Tồn cuối. Giá vốn thủ công được lưu riêng theo lô và có lịch sử điều chỉnh; số lô/hạn dùng vẫn đồng bộ với lịch sử kho.</p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Lưu thay đổi</button>
                    </div>
                </form>
            </dialog>
            <dialog id="delete-balance-{{ $row->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                <form method="POST" action="{{ route('admin.pharma.inventory.balances.destroy', $row) }}" class="rounded-2xl bg-white p-6">
                    @csrf
                    @method('DELETE')
                    <h3 class="text-lg font-bold text-rose-700">Xóa lô tồn kho?</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $row->medicine->medicine_code }} · Lô <strong>{{ $row->batch_number }}</strong></p>
                    <p class="mt-2 text-sm text-slate-600">Chỉ lô chưa phát sinh lịch sử giao dịch mới được phép xóa. Backend sẽ kiểm tra lại trước khi thực hiện.</p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Xác nhận xóa</button>
                    </div>
                </form>
            </dialog>
        @endforeach
    @endcan

    <script>
        function inventoryCheckboxes() { return Array.from(document.querySelectorAll('.inventory-row-checkbox')); }
        function selectedInventoryIds() { return inventoryCheckboxes().filter((box) => box.checked).map((box) => box.value); }
        function syncInventorySelection() {
            const boxes = inventoryCheckboxes();
            const selected = selectedInventoryIds();
            const all = document.getElementById('inventory-select-all');
            if (all) {
                all.checked = boxes.length > 0 && selected.length === boxes.length;
                all.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
            document.getElementById('inventory-selected-count').textContent = selected.length;
            const bar = document.getElementById('inventory-selection-bar');
            bar.classList.toggle('hidden', selected.length === 0);
            bar.classList.toggle('flex', selected.length > 0);
        }
        function toggleInventoryPage(checked) { inventoryCheckboxes().forEach((box) => box.checked = checked); syncInventorySelection(); }
        function clearInventorySelection() { inventoryCheckboxes().forEach((box) => box.checked = false); syncInventorySelection(); }
        function openInventoryExportModal() { if (selectedInventoryIds().length) document.getElementById('inventory-export-modal').showModal(); }
        function prepareInventoryExport(form) {
            const holder = form.querySelector('#inventory-export-ids');
            holder.innerHTML = '';
            selectedInventoryIds().forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'ids[]'; input.value = id; holder.appendChild(input);
            });
        }
    </script>


</div>
@endsection
