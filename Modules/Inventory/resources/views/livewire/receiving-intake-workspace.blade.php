<div class="space-y-6">
    @if($message)<div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $message }}</div>@endif
    @if($error)<div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $error }}</div>@endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([['Hóa đơn đã staging',$stats['snapshots']],['Đã chuẩn hóa',$stats['normalized']],['Dòng hàng',$stats['lines']],['Lỗi cần xử lý',$stats['errors']]] as [$label,$value])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($value) }}</div></div>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5"><h2 class="text-lg font-bold text-slate-900">Trung tâm tiếp nhận & chuẩn hóa nhập kho</h2><p class="mt-1 text-sm text-slate-500">Tải structured detail của hóa đơn mua vào theo hàng đợi. RAW payload được giữ nguyên; thao tác này không tạo hoặc xác nhận tồn kho.</p></div>
        <div class="grid gap-4 p-5 md:grid-cols-4">
            <label class="text-sm font-medium text-slate-700">Từ ngày<input type="date" wire:model="fromDate" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"></label>
            <label class="text-sm font-medium text-slate-700">Đến ngày<input type="date" wire:model="toDate" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"></label>
            <label class="text-sm font-medium text-slate-700">Batch size<select wire:model="batchSize" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3"><option>50</option><option>100</option><option>200</option><option>500</option></select></label>
            <div class="flex items-end"><button type="button" wire:click="dispatchIntake" wire:loading.attr="disabled" class="min-h-11 w-full rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="dispatchIntake">Đồng bộ & chuẩn hóa</span><span wire:loading wire:target="dispatchIntake">Đang đưa vào hàng đợi...</span></button></div>
        </div>
        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-600">Pipeline: Invoices → GDT detail → RAW snapshot → staging lines → deterministic normalization. Có thể chạy lại an toàn; payload hash ngăn xử lý lại dữ liệu không đổi.</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-semibold text-slate-900">Dữ liệu hàng hóa đã chuẩn hóa</h3><p class="text-sm text-slate-500">Review theo dòng hàng thay vì mở từng hóa đơn.</p></div><select wire:model.live="lineStatus" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm"><option value="all">Tất cả</option><option value="RAW">RAW</option><option value="NORMALIZED">NORMALIZED</option></select></div>
        <div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Hóa đơn / NCC</th><th class="px-4 py-3">Dữ liệu nguồn</th><th class="px-4 py-3">Tên chuẩn hóa</th><th class="px-4 py-3">Hàm lượng / dạng</th><th class="px-4 py-3">Quy cách</th><th class="px-4 py-3">Lô</th><th class="px-4 py-3">HSD</th><th class="px-4 py-3">SL / ĐVT</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($lines as $line)<tr class="align-top"><td class="px-4 py-4"><div class="font-semibold text-slate-900">#{{ $line->snapshot?->invoice?->invoice_number }}</div><div class="mt-1 max-w-48 text-xs text-slate-500">{{ $line->snapshot?->invoice?->name }}</div></td><td class="px-4 py-4"><div class="max-w-80 font-medium text-slate-800">{{ $line->raw_description }}</div></td><td class="px-4 py-4 font-medium text-slate-900">{{ $line->normalized_name ?: '—' }}</td><td class="px-4 py-4">{{ $line->strength ?: '—' }}<br><span class="text-xs text-slate-500">{{ $line->dosage_form ?: '' }}</span></td><td class="px-4 py-4">{{ $line->package_spec ?: '—' }}</td><td class="px-4 py-4">{{ $line->lot_number ?: '—' }}</td><td class="px-4 py-4">{{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</td><td class="whitespace-nowrap px-4 py-4">{{ $line->source_quantity }} {{ $line->normalized_uom ?: $line->source_uom }}</td></tr>@empty<tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">Chưa có dữ liệu staging. Chọn khoảng thời gian và bấm “Đồng bộ & chuẩn hóa”.</td></tr>@endforelse
        </tbody></table></div>
        <div class="border-t border-slate-200 p-4">{{ $lines->links('Inventory::vendor.pagination.admin-inventory') }}</div>
    </section>
</div>
