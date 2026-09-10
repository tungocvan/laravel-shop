<div class="space-y-6">
    @if ($message)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $message }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nguồn đã lưu</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['total']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">RAW detail sẵn sàng</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['detail_ready']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Thiếu / lỗi detail</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['detail_missing']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chưa phân loại</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['unclassified']) }}</p></div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-4 border-b border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tìm kiếm</span><input wire:model.live.debounce.300ms="search" type="search" placeholder="Số HĐ, MST, đơn vị, mã tra cứu..." class="min-h-11 w-full rounded-xl border-slate-300 text-sm"></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Loại hóa đơn</span><select wire:model.live="invoiceType" class="min-h-11 w-full rounded-xl border-slate-300 text-sm"><option value="purchase">Mua vào</option><option value="sold">Bán ra</option><option value="all">Tất cả</option></select></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">RAW detail</span><select wire:model.live="detailStatus" class="min-h-11 w-full rounded-xl border-slate-300 text-sm"><option value="all">Tất cả</option><option value="READY">READY</option><option value="MISSING">MISSING</option><option value="ERROR">ERROR</option><option value="FETCHING">FETCHING</option></select></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phân loại nghiệp vụ</span><select wire:model.live="businessClassification" class="min-h-11 w-full rounded-xl border-slate-300 text-sm"><option value="all">Tất cả</option>@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3">RAW nguồn</th><th class="px-4 py-3 min-w-[220px]">Phân loại nghiệp vụ</th><th class="px-4 py-3 min-w-[280px]">Ghi chú quản trị</th><th class="px-4 py-3">Thao tác</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php($invoice = $record->invoice)
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-950">#{{ $invoice?->invoice_number ?: '—' }} <span class="font-normal text-slate-500">{{ $invoice?->symbol }}</span></div>
                                <div class="mt-1 text-slate-600">{{ $invoice?->issued_date?->format('d/m/Y') }} · {{ $invoice?->name ?: 'Không rõ đơn vị' }}</div>
                                <div class="mt-1 text-xs text-slate-500">MST {{ $invoice?->tax_code ?: '—' }} · {{ $invoice?->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->header_payload ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">HEADER {{ $record->header_payload ? 'READY' : 'MISSING' }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->detail_status === 'READY' ? 'bg-emerald-50 text-emerald-700' : ($record->detail_status === 'ERROR' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">DETAIL {{ $record->detail_status }}</span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">Header: {{ optional($record->header_fetched_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                <div class="mt-1 text-xs text-slate-500">Detail: {{ optional($record->detail_fetched_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                @if ($record->last_error)<div class="mt-2 max-w-md text-xs text-rose-700">{{ $record->last_error }}</div>@endif
                            </td>
                            <td class="px-4 py-4">
                                <select wire:model="businessClassifications.{{ $record->id }}" class="min-h-11 w-full rounded-xl border-slate-300 text-sm">
                                    @foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                                </select>
                                <p class="mt-2 text-xs leading-5 text-slate-500">GOODS: hàng hóa · SERVICE_EXPENSE: dịch vụ/chi phí · MIXED: hỗn hợp · UNCLASSIFIED: cần review.</p>
                            </td>
                            <td class="px-4 py-4"><textarea wire:model="businessNotes.{{ $record->id }}" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Ví dụ: nhà cung cấp vận chuyển; hóa đơn này gồm hàng hóa + phí giao nhận..."></textarea></td>
                            <td class="px-4 py-4"><button wire:click="saveAnnotation({{ $record->id }})" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">Lưu phân loại</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Chưa có dữ liệu nguồn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">{{ $records->links() }}</div>
    </section>
</div>
