<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.inventory.dashboard') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">← Inventory Dashboard</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">Workspace vận hành với tìm kiếm, bộ lọc và phân trang có giới hạn.</p>
        </div>
        @if($canManage)
            <button type="button" wire:click="create" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tạo mới</button>
        @endif
    </header>

    @if($errorMessage)
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errorMessage }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="text-xs font-semibold text-slate-700">Tìm kiếm</label>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Mã, tên, số chứng từ..." class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>

            @if(in_array($workspace, ['receipts','issues','transfers','stocktakes']))
                <div>
                    <label class="text-xs font-semibold text-slate-700">Trạng thái</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        <option value="all">Tất cả</option><option>DRAFT</option><option>COUNTED</option><option>CONFIRMED</option><option>CANCELLED</option>
                    </select>
                </div>
            @elseif($workspace === 'warehouses')
                <div>
                    <label class="text-xs font-semibold text-slate-700">Trạng thái</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả</option><option value="active">Đang dùng</option><option value="inactive">Ngừng dùng</option>
                    </select>
                </div>
            @elseif($workspace === 'items')
                <div>
                    <label class="text-xs font-semibold text-slate-700">Hoạt động</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả</option><option value="active">Đang hoạt động</option><option value="inactive">Ngừng hoạt động</option>
                    </select>
                </div>
            @elseif($workspace === 'stock')
                <div>
                    <label class="text-xs font-semibold text-slate-700">Tồn kho</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả</option><option value="low">Tồn thấp</option><option value="positive">Còn hàng</option><option value="zero">Hết hàng</option>
                    </select>
                </div>
            @elseif($workspace === 'lots')
                <div>
                    <label class="text-xs font-semibold text-slate-700">HSD</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả</option><option value="expired">Đã hết hạn</option><option value="expiring">90 ngày tới</option><option value="valid">Còn hạn</option>
                    </select>
                </div>
            @elseif($workspace === 'movements')
                <div>
                    <label class="text-xs font-semibold text-slate-700">Loại</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả</option><option>RECEIPT</option><option>ISSUE</option><option>TRANSFER</option><option>STOCKTAKE</option><option>REVERSAL</option>
                    </select>
                </div>
            @endif

            @if(in_array($workspace, ['receipts','issues','transfers','stocktakes','stock','movements']))
                <div>
                    <label class="text-xs font-semibold text-slate-700">Kho</label>
                    <select wire:model.live="warehouseFilter" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="all">Tất cả kho</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="text-xs font-semibold text-slate-700">Số dòng</label>
                <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
        </div>
        <button type="button" wire:click="resetFilters" class="mt-3 text-xs font-semibold text-indigo-700 hover:text-indigo-900">Xóa bộ lọc</button>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        @if($workspace === 'warehouses')
                            <th class="px-4 py-3">Mã</th><th class="px-4 py-3">Kho</th><th class="px-4 py-3">Địa chỉ</th><th class="px-4 py-3">Trạng thái</th>
                        @elseif($workspace === 'items')
                            <th class="px-4 py-3">SKU</th><th class="px-4 py-3">Mặt hàng</th><th class="px-4 py-3">Trạng thái nghiệp vụ</th><th class="px-4 py-3">ĐVT</th><th class="px-4 py-3 text-right">Tồn hiện tại</th><th class="px-4 py-3">Lô / HSD</th><th class="px-4 py-3">Theo dõi</th><th class="px-4 py-3">Tồn tối thiểu</th>
                        @elseif(in_array($workspace, ['receipts','issues','stocktakes']))
                            <th class="px-4 py-3">Số phiếu</th><th class="px-4 py-3">Kho</th><th class="px-4 py-3">Ngày</th><th class="px-4 py-3">Trạng thái</th>
                        @elseif($workspace === 'transfers')
                            <th class="px-4 py-3">Số phiếu</th><th class="px-4 py-3">Kho nguồn</th><th class="px-4 py-3">Kho đích</th><th class="px-4 py-3">Trạng thái</th>
                        @elseif($workspace === 'stock')
                            <th class="px-4 py-3">Kho</th><th class="px-4 py-3">Mặt hàng</th><th class="px-4 py-3">Lô/HSD</th><th class="px-4 py-3 text-right">Tồn</th>
                        @elseif($workspace === 'lots')
                            <th class="px-4 py-3">Mặt hàng</th><th class="px-4 py-3">Lô</th><th class="px-4 py-3">HSD</th><th class="px-4 py-3">Trạng thái</th>
                        @else
                            <th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Kho</th><th class="px-4 py-3">Mặt hàng</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3 text-right">Số lượng</th><th class="px-4 py-3">Chứng từ</th>
                        @endif
                        @if($canManage || $canConfirm)<th class="px-4 py-3 text-right">Thao tác</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="align-top hover:bg-slate-50/70">
                            @if($workspace === 'warehouses')
                                <td class="px-4 py-3 font-semibold">{{ $row->code }}</td>
                                <td class="px-4 py-3">{{ $row->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row->address ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $row->is_active ? 'Đang dùng' : 'Ngừng dùng' }}</span></td>
                            @elseif($workspace === 'items')
                                @php
                                    $stockSummary = data_get($itemStockSummaries, $row->id, []);
                                    $stockDimensions = data_get($stockSummary, 'dimensions', []);
                                    $lifecycle = data_get($stockSummary, 'lifecycle', 'catalog');
                                @endphp
                                <td class="px-4 py-3 font-semibold">{{ $row->sku }}</td>
                                <td class="px-4 py-3">{{ $row->display_name }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $lifecycle === 'pending_receipt',
                                        'bg-emerald-100 text-emerald-800' => $lifecycle === 'in_stock',
                                        'bg-slate-100 text-slate-700' => in_array($lifecycle, ['catalog','zero_stock'], true),
                                        'bg-red-50 text-red-700' => $lifecycle === 'inactive',
                                    ])>{{ data_get($stockSummary, 'lifecycle_label', 'Danh mục') }}</span>
                                    @if($lifecycle === 'pending_receipt' && data_get($stockSummary, 'source.invoice_number'))
                                        <div class="mt-1 text-xs text-slate-500">HĐ #{{ data_get($stockSummary, 'source.invoice_number') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $row->base_uom }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($lifecycle === 'pending_receipt')
                                        <span class="font-semibold text-amber-700">—</span>
                                        <div class="text-[11px] text-slate-500">Chưa ghi sổ</div>
                                    @else
                                        <span class="font-bold text-slate-900">{{ data_get($stockSummary, 'total_quantity', '0') }}</span>
                                        <span class="text-xs text-slate-500">{{ $row->base_uom }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @forelse($stockDimensions as $dimension)
                                        <div @class(['mt-1' => !$loop->first])>
                                            <span class="font-semibold text-slate-800">{{ data_get($dimension, 'lot_number') ?: 'Không lô' }}</span>
                                            @if(data_get($dimension, 'expiry_date')) · HSD {{ \Illuminate\Support\Carbon::parse(data_get($dimension, 'expiry_date'))->format('d/m/Y') }} @endif
                                        </div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $row->lot_tracking ? 'Lô' : 'Không lô' }}{{ $row->expiry_tracking ? ' · HSD' : '' }}</td>
                                <td class="px-4 py-3">{{ $row->reorder_level ?? '—' }}</td>
                            @elseif(in_array($workspace, ['receipts','issues','stocktakes']))
                                <td class="px-4 py-3 font-semibold">{{ $row->number }}</td><td class="px-4 py-3">{{ $row->warehouse?->name }}</td><td class="px-4 py-3">{{ $row->document_date?->format('d/m/Y H:i') ?? $row->counted_at?->format('d/m/Y H:i') ?? '—' }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $row->status }}</span></td>
                            @elseif($workspace === 'transfers')
                                <td class="px-4 py-3 font-semibold">{{ $row->number }}</td><td class="px-4 py-3">{{ $row->sourceWarehouse?->name }}</td><td class="px-4 py-3">{{ $row->destinationWarehouse?->name }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $row->status }}</span></td>
                            @elseif($workspace === 'stock')
                                <td class="px-4 py-3">{{ $row->warehouse_code }} — {{ $row->warehouse_name }}</td><td class="px-4 py-3"><span class="font-semibold">{{ $row->sku }}</span><br><span class="text-xs text-slate-500">{{ $row->display_name }}</span></td><td class="px-4 py-3">{{ $row->lot_number ?: 'Không lô' }}@if($row->expiry_date)<br><span class="text-xs text-slate-500">HSD {{ \Illuminate\Support\Carbon::parse($row->expiry_date)->format('d/m/Y') }}</span>@endif</td><td class="px-4 py-3 text-right font-bold {{ $row->reorder_level !== null && $row->quantity_on_hand <= $row->reorder_level ? 'text-amber-700' : 'text-slate-900' }}">{{ $row->quantity_on_hand }}</td>
                            @elseif($workspace === 'lots')
                                <td class="px-4 py-3"><span class="font-semibold">{{ $row->sku }}</span><br><span class="text-xs text-slate-500">{{ $row->display_name }}</span></td><td class="px-4 py-3 font-medium">{{ $row->lot_number }}</td><td class="px-4 py-3">{{ $row->expiry_date ? \Illuminate\Support\Carbon::parse($row->expiry_date)->format('d/m/Y') : '—' }}</td><td class="px-4 py-3">{{ $row->status }}</td>
                            @else
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row->occurred_at)->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $row->warehouse_code }}</td><td class="px-4 py-3"><span class="font-semibold">{{ $row->sku }}</span><br><span class="text-xs text-slate-500">{{ $row->display_name }}</span></td><td class="px-4 py-3">{{ $row->movement_type }}</td><td class="px-4 py-3 text-right font-semibold">{{ $row->quantity_delta }} {{ $row->base_uom }}</td><td class="px-4 py-3 text-xs">{{ $row->document_type }} #{{ $row->document_id }}</td>
                            @endif

                            @if($canManage || $canConfirm)
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($workspace === 'items' && data_get($stockSummary ?? [], 'lifecycle') === 'pending_receipt' && data_get($stockSummary ?? [], 'source.inbox_id'))
                                        <a href="{{ route('admin.inventory.invoice-inbox', ['inbox' => data_get($stockSummary, 'source.inbox_id')]) }}" class="text-xs font-semibold text-amber-700 hover:text-amber-900">Xử lý nhập</a>
                                    @endif
                                    @if($canManage && (!isset($row->status) || $row->status === 'DRAFT'))
                                        <button type="button" wire:click="edit({{ $row->id }})" class="{{ $workspace === 'items' && data_get($stockSummary ?? [], 'lifecycle') === 'pending_receipt' ? 'ml-3' : '' }} text-xs font-semibold text-indigo-700 hover:text-indigo-900">Sửa</button>
                                    @endif
                                    @if($canConfirm && isset($row->status) && in_array($row->status, ['DRAFT','COUNTED']))
                                        <button type="button" wire:click="askConfirm({{ $row->id }})" class="ml-3 text-xs font-semibold text-emerald-700 hover:text-emerald-900">Xác nhận</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-slate-500">Không có dữ liệu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-4">{{ $rows->links('Inventory::vendor.pagination.admin-inventory') }}</div>
    </section>

    @if($formOpen)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true">
            <div class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-bold text-slate-950">{{ $editingId ? 'Cập nhật' : 'Tạo mới' }} — {{ $title }}</h2>
                    <button type="button" wire:click="$set('formOpen', false)" class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>

                <form wire:submit="save" class="space-y-5 p-5">
                    @if($workspace === 'warehouses')
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium">Mã kho<input wire:model="form.code" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></label>
                            <label class="text-sm font-medium">Tên kho<input wire:model="form.name" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            <label class="text-sm font-medium md:col-span-2">Địa chỉ<textarea wire:model="form.address" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></textarea></label>
                            <label class="text-sm font-medium">Mã tỉnh/thành<input wire:model="form.province_code" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            <label class="flex items-center gap-2 pt-8 text-sm font-medium"><input type="checkbox" wire:model="form.is_active"> Đang hoạt động</label>
                        </div>
                    @elseif($workspace === 'items')
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium">SKU<input wire:model="form.sku" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            <label class="text-sm font-medium">Tên mặt hàng<input wire:model="form.display_name" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            <label class="text-sm font-medium">ĐVT cơ sở<input wire:model="form.base_uom" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            <label class="text-sm font-medium">Tồn tối thiểu<input type="number" step="0.000001" min="0" wire:model="form.reorder_level" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                        </div>

                        @if($editingId)
                            @php
                                $itemLifecycle = data_get($itemInventorySummary, 'lifecycle', 'catalog');
                                $itemSource = data_get($itemInventorySummary, 'source');
                                $package = data_get($itemInventorySummary, 'packaging');
                            @endphp

                            <section @class([
                                'rounded-2xl border p-4',
                                'border-amber-200 bg-amber-50' => $itemLifecycle === 'pending_receipt',
                                'border-emerald-200 bg-emerald-50' => $itemLifecycle === 'in_stock',
                                'border-slate-200 bg-slate-50' => !in_array($itemLifecycle, ['pending_receipt','in_stock'], true),
                            ])>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái nghiệp vụ</div>
                                        <div class="mt-1 font-bold text-slate-900">{{ data_get($itemInventorySummary, 'lifecycle_label', 'Danh mục') }}</div>
                                        @if($itemLifecycle === 'pending_receipt')
                                            <p class="mt-2 text-sm text-amber-800">Mặt hàng đã được tạo để đối chiếu hóa đơn nhưng phiếu nhập chưa được xác nhận. Tồn kho chưa thay đổi.</p>
                                            @if($itemSource)
                                                <div class="mt-2 text-xs text-slate-600">Hóa đơn #{{ data_get($itemSource, 'invoice_number', '—') }}@if(data_get($itemSource, 'seller_name')) · {{ data_get($itemSource, 'seller_name') }}@endif</div>
                                            @endif
                                        @endif
                                    </div>
                                    @if($itemLifecycle === 'pending_receipt' && data_get($itemSource, 'inbox_id'))
                                        <a href="{{ route('admin.inventory.invoice-inbox', ['inbox' => data_get($itemSource, 'inbox_id')]) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 text-sm font-semibold text-amber-800 hover:bg-amber-50">Xử lý nhập kho</a>
                                    @endif
                                </div>
                            </section>

                            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-bold text-slate-900">Thông tin tồn kho hiện tại</h3>
                                        <p class="mt-1 text-xs text-slate-600">Dữ liệu chỉ đọc từ sổ kho. Số lượng, lô và HSD không sửa trực tiếp tại danh mục mặt hàng.</p>
                                    </div>
                                    <div class="rounded-xl border border-emerald-200 bg-white px-4 py-2 text-right">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Tổng tồn</div>
                                        <div class="mt-1 text-lg font-bold text-slate-900">
                                            @if($itemLifecycle === 'pending_receipt')
                                                — <span class="text-xs font-medium text-slate-500">Chưa ghi sổ</span>
                                            @else
                                                {{ data_get($itemInventorySummary, 'total_quantity', '0') }} {{ data_get($itemInventorySummary, 'base_uom', data_get($form, 'base_uom')) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if(data_get($package, 'package_uom') && data_get($package, 'quantity'))
                                    <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-slate-700">
                                        <span class="font-semibold text-slate-900">Quy cách đóng gói:</span>
                                        1 {{ data_get($package, 'package_uom') }} = {{ rtrim(rtrim(number_format((float) data_get($package, 'quantity'), 8, '.', ''), '0'), '.') }} {{ data_get($package, 'base_uom', data_get($form, 'base_uom')) }}
                                    </div>
                                @endif

                                <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,.8fr)] gap-3 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <div>Kho</div><div>Lô</div><div>Hạn sử dụng</div><div class="text-right">Tồn</div>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        @forelse(data_get($itemInventorySummary, 'dimensions', []) as $dimension)
                                            <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,.8fr)] gap-3 px-4 py-3 text-sm">
                                                <div><div class="font-semibold text-slate-900">{{ data_get($dimension, 'warehouse_code') }}</div><div class="text-xs text-slate-500">{{ data_get($dimension, 'warehouse_name') }}</div></div>
                                                <div class="font-medium text-slate-800">{{ data_get($dimension, 'lot_number') ?: 'Không lô' }}</div>
                                                <div>@if(data_get($dimension, 'expiry_date')){{ \Illuminate\Support\Carbon::parse(data_get($dimension, 'expiry_date'))->format('d/m/Y') }}@else—@endif</div>
                                                <div class="text-right font-bold text-slate-900">{{ data_get($dimension, 'quantity') }} {{ data_get($itemInventorySummary, 'base_uom') }}</div>
                                            </div>
                                        @empty
                                            <div class="px-4 py-6 text-center text-sm text-slate-500">
                                                {{ $itemLifecycle === 'pending_receipt' ? 'Chưa có tồn kho vì phiếu nhập chưa được xác nhận.' : 'Mặt hàng chưa có tồn kho.' }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </section>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach(['lot_tracking'=>'Theo dõi lô','expiry_tracking'=>'Theo dõi HSD','allow_fractional_quantity'=>'Cho phép số lẻ','is_active'=>'Đang hoạt động'] as $key=>$label)
                                <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="form.{{ $key }}"> {{ $label }}</label>
                            @endforeach
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium">Số chứng từ<input wire:model="form.number" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                            @if($workspace === 'transfers')
                                <label class="text-sm font-medium">Kho nguồn<select wire:model="form.source_warehouse_id" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label>
                                <label class="text-sm font-medium">Kho đích<select wire:model="form.destination_warehouse_id" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label>
                            @else
                                <label class="text-sm font-medium">Kho<select wire:model="form.warehouse_id" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label>
                            @endif
                            @if($workspace !== 'stocktakes')<label class="text-sm font-medium">Ngày chứng từ<input type="datetime-local" wire:model="form.document_date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>@endif
                            <label class="text-sm font-medium md:col-span-2">Ghi chú<textarea wire:model="form.notes" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></textarea></label>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between"><h3 class="font-semibold">Dòng hàng</h3><button type="button" wire:click="addLine" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">+ Thêm dòng</button></div>
                            @foreach($lines as $index=>$line)
                                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-2 xl:grid-cols-6">
                                    <label class="text-xs font-semibold xl:col-span-2">Mặt hàng<select wire:model="lines.{{ $index }}.inventory_item_id" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"><option value="">Chọn mặt hàng</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }} ({{ $item->base_uom }})</option>@endforeach</select></label>
                                    <label class="text-xs font-semibold">Số lượng<input type="number" step="0.000001" min="0" wire:model="lines.{{ $index }}.quantity" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"></label>
                                    <label class="text-xs font-semibold">Lô đã có<select wire:model="lines.{{ $index }}.lot_id" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"><option value="">Không chọn</option>@foreach($lots as $lot)<option value="{{ $lot->id }}">{{ $lot->lot_number }}{{ $lot->expiry_date ? ' · '.$lot->expiry_date->format('d/m/Y') : '' }}</option>@endforeach</select></label>
                                    @if($workspace === 'receipts')
                                        <label class="text-xs font-semibold">Lô mới<input wire:model="lines.{{ $index }}.lot_number" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"></label>
                                        <label class="text-xs font-semibold">HSD<input type="date" wire:model="lines.{{ $index }}.expiry_date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"></label>
                                        <label class="text-xs font-semibold">Đơn giá<input type="number" step="0.000001" min="0" wire:model="lines.{{ $index }}.unit_cost" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"></label>
                                    @endif
                                    <div class="flex items-end"><button type="button" wire:click="removeLine({{ $index }})" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Xóa dòng</button></div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="$set('formOpen', false)" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Hủy</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60">{{ $workspace === 'items' ? 'Lưu thay đổi' : 'Lưu nháp' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($confirmingId)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-bold text-slate-950">Xác nhận ghi sổ tồn kho?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Thao tác này sẽ gọi posting service, tạo stock movements và cập nhật balance. Chứng từ sau khi xác nhận là bất biến.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" wire:click="$set('confirmingId', null)" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Hủy</button>
                    <button type="button" wire:click="confirmDocument" wire:loading.attr="disabled" wire:target="confirmDocument" class="min-h-11 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Xác nhận nhập sổ</button>
                </div>
            </div>
        </div>
    @endif
</div>
