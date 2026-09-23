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

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="font-semibold text-slate-950">Excel tồn kho</h2>
                <p class="mt-1 text-sm text-slate-500">Tải file mẫu để nhập tồn đầu kỳ hàng loạt hoặc xuất trạng thái tồn kho hiện tại.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.inventory.opening.template') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Tải file mẫu</a>
                <a href="{{ route('admin.pharma.inventory.export') }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800">Export tồn kho</a>
            </div>
        </div>
        @can('create_pharma')
            <form method="POST" action="{{ route('admin.pharma.inventory.opening.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 md:flex-row md:items-center">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="min-h-11 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Import tồn đầu kỳ</button>
            </form>
        @endcan
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Giá trị tồn theo giá vốn NCC</p>
            <p class="mt-2 text-2xl font-bold text-emerald-950">{{ number_format($totalInventoryValue, 0, ',', '.') }} đ</p>
            <p class="mt-1 text-xs text-emerald-700">Giá vốn trung bình từ Supplier Tracking đang hiệu lực.</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Lô chưa định giá</p>
            <p class="mt-2 text-2xl font-bold text-amber-950">{{ number_format($unpricedBalanceCount) }}</p>
            <p class="mt-1 text-xs text-amber-700">Các lô còn tồn nhưng chưa có giá vốn NCC đang hiệu lực.</p>
        </div>
    </section>

    <form method="GET"
          x-data="{ searchTimer: null, submitFilters() { this.$el.requestSubmit() }, searchChanged() { clearTimeout(this.searchTimer); this.searchTimer = setTimeout(() => this.submitFilters(), 450) } }"
          class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(240px,1fr)_auto_auto_auto_auto_auto]">
        <input name="q" value="{{ request('q') }}" @input="searchChanged()" placeholder="Tìm mã thuốc / tên thuốc" class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm">
        <select name="expiry_warning" @change="submitFilters()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Tất cả cảnh báo</option>
            <option value="expired" @selected(request('expiry_warning') === 'expired')>Đã hết hạn</option>
            <option value="lt1" @selected(request('expiry_warning') === 'lt1')>Còn dưới 1 tháng</option>
            <option value="lt3" @selected(request('expiry_warning') === 'lt3')>Còn dưới 3 tháng</option>
            <option value="lt6" @selected(request('expiry_warning') === 'lt6')>Còn dưới 6 tháng</option>
            <option value="safe" @selected(request('expiry_warning') === 'safe')>Không cảnh báo (≥ 6 tháng)</option>
        </select>
        <select name="cost_status" @change="submitFilters()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Tất cả giá vốn</option>
            <option value="priced" @selected(request('cost_status') === 'priced')>Đã có giá vốn</option>
            <option value="unpriced" @selected(request('cost_status') === 'unpriced')>Chưa có giá vốn</option>
        </select>
        <select name="value_sort" @change="submitFilters()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Sắp xếp mặc định</option>
            <option value="value_desc" @selected(request('value_sort') === 'value_desc')>Giá trị tồn: lớn nhất</option>
            <option value="value_asc" @selected(request('value_sort') === 'value_asc')>Giá trị tồn: nhỏ nhất</option>
        </select>
        <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm">
            <input type="checkbox" name="in_stock" value="1" @change="submitFilters()" @checked(request('in_stock'))> Chỉ còn tồn
        </label>
        <a href="{{ route('admin.pharma.inventory.index') }}" class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Xóa bộ lọc</a>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Mã thuốc</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Số lô</th>
                        <th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn đầu</th>
                        <th class="px-4 py-3 text-right">Tồn cuối</th><th class="px-4 py-3 text-right">Giá vốn NCC TB</th><th class="px-4 py-3 text-right">Giá trị tồn</th><th class="px-4 py-3">Cảnh báo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($balances as $row)
                        <tr>
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
                                    <div class="text-xs text-slate-500">{{ $row->supplier_cost_count }} nguồn</div>
                                @else
                                    <a href="{{ route('admin.pharma.supplier-trackings.index', ['medicineId' => $row->medicine_id]) }}" class="text-xs font-semibold text-amber-700 underline">Chưa có giá vốn</a>
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
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Chưa có tồn kho. Hãy lập và ghi sổ phiếu nhập.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $balances->links() }}</div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Phiếu nhập gần đây</h2>
            <div class="mt-3 space-y-2">
                @forelse($receipts as $doc)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3">
                        <div>
                            <div class="font-mono text-xs font-bold">{{ $doc->number }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $doc->receipt_date->format('d/m/Y') }} · {{ $doc->items_count }} dòng · {{ strtoupper($doc->status) }}</div>
                        </div>
                        @can('edit_pharma')
                            @if($doc->status === 'draft')
                                <form method="POST" action="{{ route('admin.pharma.inventory.receipts.post', $doc) }}">
                                    @csrf
                                    <button onclick="return confirm('Ghi sổ phiếu nhập này?')" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Ghi sổ</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có phiếu nhập.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Phiếu xuất gần đây</h2>
            <div class="mt-3 space-y-2">
                @forelse($issues as $doc)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3">
                        <div>
                            <div class="font-mono text-xs font-bold">{{ $doc->number }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $doc->issue_date->format('d/m/Y') }} · {{ $doc->items_count }} dòng · {{ strtoupper($doc->status) }}</div>
                        </div>
                        @can('edit_pharma')
                            @if($doc->status === 'draft')
                                <form method="POST" action="{{ route('admin.pharma.inventory.issues.post', $doc) }}">
                                    @csrf
                                    <button onclick="return confirm('Ghi sổ phiếu xuất này?')" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white">Ghi sổ</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có phiếu xuất.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
