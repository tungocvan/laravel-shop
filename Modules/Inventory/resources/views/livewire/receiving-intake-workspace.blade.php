<div class="space-y-6">
    @if($message)<div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $message }}</div>@endif
    @if($error)<div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $error }}</div>@endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([['Hóa đơn đã staging',$stats['snapshots']],['Đã chuẩn hóa',$stats['normalized']],['Dòng hàng',$stats['lines']],['Lỗi cần xử lý',$stats['errors']]] as [$label,$value])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($value) }}</div></div>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5"><h2 class="text-lg font-bold text-slate-900">Trung tâm tiếp nhận & chuẩn hóa nhập kho</h2><p class="mt-1 text-sm text-slate-500">Invoices sở hữu RAW GDT detail. Inventory chỉ nhận dữ liệu đã chuẩn hóa để review và tạo DRAFT; không tự động cộng tồn.</p></div>
        <div class="grid gap-4 p-5 md:grid-cols-4">
            <label class="text-sm font-medium text-slate-700">Từ ngày<input type="date" wire:model="fromDate" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"></label>
            <label class="text-sm font-medium text-slate-700">Đến ngày<input type="date" wire:model="toDate" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"></label>
            <label class="text-sm font-medium text-slate-700">Batch size<select wire:model="batchSize" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"><option>50</option><option>100</option><option>200</option><option>500</option></select></label>
            <div class="flex items-end"><button type="button" wire:click="dispatchIntake" wire:loading.attr="disabled" class="min-h-11 w-full rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Đồng bộ & chuẩn hóa</button></div>
        </div>
        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-600">1. Tải chi tiết → 2. Chuẩn hóa → 3. Chọn hóa đơn → 4. Publish Inbox / Review ngoại lệ → 5. Tạo Receipt DRAFT → 6. Người dùng xác nhận nhập kho.</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Publish & tạo phiếu nhập hàng loạt</h3><p class="mt-1 text-sm text-slate-500">Tối đa 100 snapshot NORMALIZED gần nhất. Mỗi hóa đơn tạo tối đa một Receipt DRAFT để giữ audit trail.</p></div>
        <div class="max-h-72 overflow-auto divide-y divide-slate-100">
            @forelse($readySnapshots as $snapshot)
                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 hover:bg-slate-50"><input type="checkbox" wire:model="selectedSnapshots" value="{{ $snapshot->id }}" class="mt-1 rounded border-gray-300"><span><span class="font-semibold text-slate-900">#{{ $snapshot->invoice?->invoice_number }}</span><span class="ml-2 text-xs text-slate-500">{{ $snapshot->invoice?->issued_date?->format('d/m/Y') }}</span><span class="block text-sm text-slate-600">{{ $snapshot->invoice?->name }}</span></span></label>
            @empty<div class="px-5 py-8 text-center text-sm text-slate-500">Chưa có snapshot NORMALIZED.</div>@endforelse
        </div>
        <div class="grid gap-3 border-t border-slate-200 p-5 lg:grid-cols-[1fr_auto_auto]">
            <select wire:model="warehouseId" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 text-sm"><option value="">Chọn kho nhận hàng</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select>
            <button type="button" wire:click="publishSelected" wire:loading.attr="disabled" class="min-h-11 rounded-xl border border-indigo-300 bg-white px-5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50">Publish sang Inbox</button>
            <button type="button" wire:click="createDraftReceipts" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Tạo Receipt DRAFT</button>
        </div>
        <div class="border-t border-amber-200 bg-amber-50 px-5 py-3 text-xs font-medium text-amber-900">Nếu còn dòng UNRESOLVED, hệ thống dừng tạo DRAFT và yêu cầu xử lý tại “Hóa đơn chờ nhập kho”. Không có thao tác xác nhận tồn kho tại màn hình này.</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-semibold text-slate-900">Dữ liệu hàng hóa đã chuẩn hóa</h3><p class="text-sm text-slate-500">Review theo dòng hàng; tên/quy cách/lô/HSD được tách riêng.</p></div><select wire:model.live="lineStatus" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm"><option value="all">Tất cả</option><option value="RAW">RAW</option><option value="NORMALIZED">NORMALIZED</option></select></div>
        <div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Hóa đơn / NCC</th><th class="px-4 py-3">Dữ liệu nguồn</th><th class="px-4 py-3">Tên chuẩn hóa</th><th class="px-4 py-3">Hàm lượng / dạng</th><th class="px-4 py-3">Quy cách</th><th class="px-4 py-3">Lô</th><th class="px-4 py-3">HSD</th><th class="px-4 py-3">SL / ĐVT</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($lines as $line)<tr class="align-top"><td class="px-4 py-4"><div class="font-semibold text-slate-900">#{{ $line->snapshot?->invoice?->invoice_number }}</div><div class="mt-1 max-w-48 text-xs text-slate-500">{{ $line->snapshot?->invoice?->name }}</div></td><td class="px-4 py-4"><div class="max-w-80 font-medium text-slate-800">{{ $line->raw_description }}</div></td><td class="px-4 py-4 font-medium text-slate-900">{{ $line->normalized_name ?: '—' }}</td><td class="px-4 py-4">{{ $line->strength ?: '—' }}<br><span class="text-xs text-slate-500">{{ $line->dosage_form ?: '' }}</span></td><td class="px-4 py-4">{{ $line->package_spec ?: '—' }}</td><td class="px-4 py-4">{{ $line->lot_number ?: '—' }}</td><td class="px-4 py-4">{{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</td><td class="whitespace-nowrap px-4 py-4">{{ $line->source_quantity }} {{ $line->normalized_uom ?: $line->source_uom }}</td></tr>@empty<tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">Chưa có dữ liệu staging.</td></tr>@endforelse
        </tbody></table></div>
        <div class="border-t border-slate-200 p-4">{{ $lines->links('Inventory::vendor.pagination.admin-inventory') }}</div>
    </section>
</div>
