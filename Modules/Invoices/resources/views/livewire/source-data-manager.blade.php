<div class="space-y-6">
    @php
        $classificationLabels = [
            'GOODS' => 'Hàng hóa',
            'SERVICE_EXPENSE' => 'Dịch vụ / Chi phí',
            'MIXED' => 'Hỗn hợp',
            'UNCLASSIFIED' => 'Chưa phân loại',
        ];
        $detailLabels = [
            'READY' => 'Sẵn sàng',
            'MISSING' => 'Thiếu dữ liệu',
            'ERROR' => 'Lỗi',
            'FETCHING' => 'Đang xử lý',
        ];
        $hasFilters = $search !== '' || $year !== 'all' || $month !== 'all' || $invoiceType !== 'purchase' || $detailStatus !== 'all' || $businessClassification !== 'all' || $perPage !== 25;
        $controlClass = 'min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    @endphp

    @if ($message)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $message }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nguồn đã lưu</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['total']) }}</p></div>
        <div class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chi tiết sẵn sàng</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['detail_ready']) }}</p></div>
        <div class="rounded-2xl border {{ $stats['detail_missing'] > 0 ? 'border-amber-200' : 'border-slate-200' }} bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Thiếu / lỗi chi tiết</p><p class="mt-2 text-2xl font-bold {{ $stats['detail_missing'] > 0 ? 'text-amber-700' : 'text-slate-950' }}">{{ number_format($stats['detail_missing']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chưa phân loại</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['unclassified']) }}</p></div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <label class="block md:col-span-2 xl:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tìm kiếm</span>
                    <x-search wire:model.live.debounce.300ms="search" placeholder="Tìm nhà cung cấp, MST, số hóa đơn, mã tra cứu..." inputClass="min-h-11 rounded-xl border-slate-300 shadow-sm" />
                </label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Năm</span><select wire:model.live="year" class="{{ $controlClass }}"><option value="all">Tất cả</option>@foreach ($availableYears as $availableYear)<option value="{{ $availableYear }}">{{ $availableYear }}</option>@endforeach</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tháng</span><select wire:model.live="month" class="{{ $controlClass }}"><option value="all">Tất cả</option>@for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}">Tháng {{ $m }}</option>@endfor</select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Loại hóa đơn</span><select wire:model.live="invoiceType" class="{{ $controlClass }}"><option value="purchase">Mua vào</option><option value="sold">Bán ra</option><option value="all">Tất cả</option></select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái chi tiết</span><select wire:model.live="detailStatus" class="{{ $controlClass }}"><option value="all">Tất cả</option><option value="READY">Sẵn sàng</option><option value="MISSING">Thiếu dữ liệu</option><option value="ERROR">Lỗi</option><option value="FETCHING">Đang xử lý</option></select></label>
            </div>
            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-end sm:justify-between">
                <label class="block sm:w-72"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phân loại nghiệp vụ</span><select wire:model.live="businessClassification" class="{{ $controlClass }}"><option value="all">Tất cả</option>@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select></label>
                @if ($hasFilters)
                    <button type="button" wire:click="resetFilters" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Xóa bộ lọc</button>
                @endif
            </div>
        </div>

        <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3 sm:px-5">
            <p class="text-sm font-semibold text-slate-800">Phân loại nghiệp vụ</p>
            <p class="mt-1 text-xs leading-5 text-slate-600"><span class="font-semibold">Hàng hóa</span> — hàng tồn kho/vật tư/sản phẩm · <span class="font-semibold">Dịch vụ / Chi phí</span> — dịch vụ, phí, chi phí · <span class="font-semibold">Hỗn hợp</span> — nhiều nhóm · <span class="font-semibold">Chưa phân loại</span> — cần admin review.</p>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3">Trạng thái nguồn</th><th class="min-w-[250px] px-4 py-3">Phân loại</th><th class="min-w-[240px] px-4 py-3">Ghi chú</th><th class="px-4 py-3">Thao tác</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php($invoice = $record->invoice)
                        <tr class="align-top hover:bg-slate-50/50">
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-950">#{{ $invoice?->invoice_number ?: '—' }} <span class="font-medium text-slate-500">{{ $invoice?->symbol }}</span></div>
                                <div class="mt-1 font-medium text-slate-800">{{ $invoice?->name ?: 'Không rõ nhà cung cấp' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $invoice?->issued_date?->format('d/m/Y') ?: '—' }} · MST {{ $invoice?->tax_code ?: '—' }} · {{ $invoice?->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->header_payload ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $record->header_payload ? 'Header đã lưu' : 'Thiếu header' }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->detail_status === 'READY' ? 'bg-emerald-50 text-emerald-700' : ($record->detail_status === 'ERROR' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $record->detail_status === 'READY' ? 'Chi tiết sẵn sàng' : ($detailLabels[$record->detail_status] ?? 'Chưa rõ') }}</span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">Header: {{ optional($record->header_fetched_at)->format('d/m/Y H:i') ?: '—' }} · Chi tiết: {{ optional($record->detail_fetched_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                @if ($record->last_error)<div class="mt-2 max-w-md text-xs font-medium text-rose-700">{{ $record->last_error }}</div>@endif
                            </td>
                            <td class="px-4 py-4">
                                <select wire:model="businessClassifications.{{ $record->id }}" class="{{ $controlClass }}">@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select>
                                <label class="mt-3 flex items-start gap-2 text-xs font-medium text-slate-700"><input type="checkbox" wire:model="applySameTaxCode.{{ $record->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Áp dụng cho toàn bộ nhà cung cấp<span class="mt-0.5 block font-normal text-slate-500">MST {{ $invoice?->tax_code ?: '—' }} · các hóa đơn cùng loại sẽ dùng quy tắc này</span></span></label>
                            </td>
                            <td class="px-4 py-4"><textarea wire:model="businessNotes.{{ $record->id }}" rows="2" maxlength="2000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Ghi chú quản trị..."></textarea></td>
                            <td class="px-4 py-4"><button type="button" wire:click="saveAnnotation({{ $record->id }})" wire:loading.attr="disabled" wire:target="saveAnnotation({{ $record->id }})" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="saveAnnotation({{ $record->id }})">Lưu</span><span wire:loading wire:target="saveAnnotation({{ $record->id }})">Đang lưu...</span></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">Chưa có dữ liệu nguồn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-200 lg:hidden">
            @forelse ($records as $record)
                @php($invoice = $record->invoice)
                <article class="space-y-4 p-4 sm:p-5">
                    <div><div class="font-bold text-slate-950">#{{ $invoice?->invoice_number ?: '—' }} <span class="font-medium text-slate-500">{{ $invoice?->symbol }}</span></div><div class="mt-1 font-semibold text-slate-800">{{ $invoice?->name ?: 'Không rõ nhà cung cấp' }}</div><div class="mt-1 text-xs text-slate-500">{{ $invoice?->issued_date?->format('d/m/Y') ?: '—' }} · MST {{ $invoice?->tax_code ?: '—' }} · {{ $invoice?->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</div></div>
                    <div class="flex flex-wrap gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->header_payload ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $record->header_payload ? 'Header đã lưu' : 'Thiếu header' }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->detail_status === 'READY' ? 'bg-emerald-50 text-emerald-700' : ($record->detail_status === 'ERROR' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $record->detail_status === 'READY' ? 'Chi tiết sẵn sàng' : ($detailLabels[$record->detail_status] ?? 'Chưa rõ') }}</span></div>
                    @if ($record->last_error)<div class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">{{ $record->last_error }}</div>@endif
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phân loại</span><select wire:model="businessClassifications.{{ $record->id }}" class="{{ $controlClass }}">@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select></label>
                    <label class="flex items-start gap-2 text-xs font-medium text-slate-700"><input type="checkbox" wire:model="applySameTaxCode.{{ $record->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Áp dụng cho toàn bộ nhà cung cấp<span class="mt-0.5 block font-normal text-slate-500">MST {{ $invoice?->tax_code ?: '—' }} · các hóa đơn cùng loại</span></span></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Ghi chú quản trị</span><textarea wire:model="businessNotes.{{ $record->id }}" rows="2" maxlength="2000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Ghi chú quản trị..."></textarea></label>
                    <button type="button" wire:click="saveAnnotation({{ $record->id }})" wire:loading.attr="disabled" wire:target="saveAnnotation({{ $record->id }})" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-60"><span wire:loading.remove wire:target="saveAnnotation({{ $record->id }})">Lưu</span><span wire:loading wire:target="saveAnnotation({{ $record->id }})">Đang lưu...</span></button>
                </article>
            @empty
                <div class="px-4 py-12 text-center text-sm text-slate-500">Chưa có dữ liệu nguồn phù hợp bộ lọc.</div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 px-4 py-4 sm:px-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <p class="text-sm text-slate-600">Hiển thị <span class="font-semibold text-slate-900">{{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}</span> / <span class="font-semibold text-slate-900">{{ number_format($records->total()) }}</span> bản ghi</p>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-600"><span class="hidden sm:inline">Số dòng</span><select wire:model.live="perPage" class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="25">25 / trang</option><option value="50">50 / trang</option><option value="100">100 / trang</option></select></label>
            </div>
            {{ $records->links('Invoices::vendor.pagination.admin-source-data') }}
        </div>
    </section>
</div>
