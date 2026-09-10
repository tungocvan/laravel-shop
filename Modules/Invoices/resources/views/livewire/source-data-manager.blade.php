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
        $hasFilters = $search !== '' || $partner !== '' || $year !== 'all' || $month !== 'all' || $invoiceType !== 'purchase' || $detailStatus !== 'all' || $businessClassification !== 'all' || $perPage !== 25;
        $controlClass = 'h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400';
    @endphp

    @if ($message)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ $message }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Tổng quan theo bộ lọc kỳ dữ liệu</p>
                <h2 class="mt-1 text-base font-bold text-gray-900">{{ $statsScopeLabel }}</h2>
            </div>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">Danh sách hiện tại: {{ number_format($records->total()) }} hóa đơn</span>
        </div>
        <div class="grid gap-px bg-gray-200 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nguồn đã lưu</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['total']) }}</p><p class="mt-1 text-xs text-slate-500">Trong kỳ/đối tác đang chọn</p></div>
            <div class="bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chi tiết sẵn sàng</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['detail_ready']) }}</p><p class="mt-1 text-xs text-slate-500">Có thể xử lý downstream</p></div>
            <div class="bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Thiếu / lỗi chi tiết</p><p class="mt-2 text-2xl font-bold {{ $stats['detail_missing'] > 0 ? 'text-amber-700' : 'text-slate-950' }}">{{ number_format($stats['detail_missing']) }}</p><p class="mt-1 text-xs text-slate-500">Cần kiểm tra dữ liệu nguồn</p></div>
            <div class="bg-white p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Chưa phân loại</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($stats['unclassified']) }}</p><p class="mt-1 text-xs text-slate-500">Cần admin review</p></div>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Bộ lọc dữ liệu nguồn</h2>
                    <p class="mt-1 text-sm text-gray-500">Chọn kỳ dữ liệu trước, sau đó tìm nhà cung cấp và thu hẹp theo trạng thái xử lý.</p>
                </div>
                @if ($hasFilters)
                    <button type="button" wire:click="resetFilters" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">Xóa bộ lọc</button>
                @endif
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Kỳ dữ liệu</h3>
                    <p class="mt-1 text-xs text-slate-500">Các thẻ tổng quan phía trên tự động tính lại theo năm, tháng và loại hóa đơn đang chọn.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Năm</span><select wire:model.live="year" class="{{ $controlClass }}"><option value="all">Tất cả các năm</option>@foreach ($availableYears as $availableYear)<option value="{{ $availableYear }}">{{ $availableYear }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tháng</span><select wire:model.live="month" class="{{ $controlClass }}"><option value="all">Tất cả các tháng</option>@for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}">Tháng {{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>@endfor</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Loại hóa đơn</span><select wire:model.live="invoiceType" class="{{ $controlClass }}"><option value="purchase">Mua vào</option><option value="sold">Bán ra</option><option value="all">Tất cả loại</option></select></label>
                </div>
            </section>

            <section>
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Điều kiện lọc</h3>
                    <p class="mt-1 text-xs text-slate-500">Tìm nhà cung cấp theo đúng kiểu tìm đối tác tại Danh sách hóa đơn; từ khóa dùng cho số hóa đơn, ký hiệu, MST hoặc mã tra cứu.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="sm:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Nhà cung cấp</span>
                        <x-select-search id="source-data-partner-search" wire:model="partner" options-wire="partnerList" placeholder="Tìm nhà cung cấp...">
                            <option value="">Tất cả nhà cung cấp</option>
                            @foreach ($partnerList as $item)<option value="{{ $item }}" @selected($partner === $item)>{{ $item }}</option>@endforeach
                        </x-select-search>
                    </div>
                    <label class="block sm:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Từ khóa hóa đơn</span>
                        <x-search wire:model.live.debounce.300ms="search" placeholder="Số HĐ, ký hiệu, MST, mã tra cứu..." inputClass="h-11 rounded-xl border-gray-300 shadow-sm" />
                    </label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Trạng thái chi tiết</span><select wire:model.live="detailStatus" class="{{ $controlClass }}"><option value="all">Mọi trạng thái</option><option value="READY">Sẵn sàng</option><option value="MISSING">Thiếu dữ liệu</option><option value="ERROR">Lỗi</option><option value="FETCHING">Đang xử lý</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Phân loại nghiệp vụ</span><select wire:model.live="businessClassification" class="{{ $controlClass }}"><option value="all">Mọi phân loại</option>@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select></label>
                    <label class="block sm:max-w-40"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Hiển thị</span><select wire:model.live="perPage" class="{{ $controlClass }}"><option value="25">25 / trang</option><option value="50">50 / trang</option><option value="100">100 / trang</option></select></label>
                </div>
            </section>

            <details class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-600">
                <summary class="cursor-pointer select-none font-semibold text-slate-700">Hướng dẫn phân loại nghiệp vụ</summary>
                <p class="mt-2 text-xs leading-5"><span class="font-semibold">Hàng hóa</span> — hàng tồn kho/vật tư/sản phẩm · <span class="font-semibold">Dịch vụ / Chi phí</span> — dịch vụ, phí, chi phí · <span class="font-semibold">Hỗn hợp</span> — nhiều nhóm · <span class="font-semibold">Chưa phân loại</span> — cần admin review.</p>
            </details>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3">Trạng thái nguồn</th><th class="min-w-[260px] px-4 py-3">Phân loại</th><th class="min-w-[170px] px-4 py-3">Ghi chú</th><th class="px-4 py-3">Thao tác</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $record)
                        @php($invoice = $record->invoice)
                        <tr class="align-top transition hover:bg-gray-50/70">
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-950">#{{ $invoice?->invoice_number ?: '—' }} <span class="font-medium text-slate-500">{{ $invoice?->symbol }}</span></div>
                                <div class="mt-1 max-w-sm font-medium text-slate-800">{{ $invoice?->name ?: 'Không rõ nhà cung cấp' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $invoice?->issued_date?->format('d/m/Y') ?: '—' }} · MST {{ $invoice?->tax_code ?: '—' }} · {{ $invoice?->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->header_payload ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $record->header_payload ? 'Header đã lưu' : 'Thiếu header' }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->detail_status === 'READY' ? 'bg-emerald-50 text-emerald-700' : ($record->detail_status === 'ERROR' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $record->detail_status === 'READY' ? 'Chi tiết sẵn sàng' : ($detailLabels[$record->detail_status] ?? 'Chưa rõ') }}</span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">Header: {{ optional($record->header_fetched_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">Chi tiết: {{ optional($record->detail_fetched_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                @if ($record->last_error)<div class="mt-2 max-w-md text-xs font-medium text-rose-700">{{ $record->last_error }}</div>@endif
                            </td>
                            <td class="px-4 py-4">
                                <select wire:model="businessClassifications.{{ $record->id }}" class="{{ $controlClass }}">@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select>
                                <label class="mt-3 flex items-start gap-2 text-xs font-medium text-slate-700"><input type="checkbox" wire:model="applySameTaxCode.{{ $record->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Áp dụng cho toàn bộ nhà cung cấp<span class="mt-0.5 block font-normal text-slate-500">MST {{ $invoice?->tax_code ?: '—' }} · các hóa đơn cùng loại</span></span></label>
                            </td>
                            <td class="px-4 py-4">
                                <details class="group w-full">
                                    <summary class="inline-flex min-h-9 cursor-pointer select-none items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                        {{ trim((string) ($businessNotes[$record->id] ?? '')) !== '' ? 'Sửa ghi chú' : 'Thêm ghi chú' }}
                                        @if (trim((string) ($businessNotes[$record->id] ?? '')) !== '')<span class="h-2 w-2 rounded-full bg-indigo-500" title="Đã có ghi chú"></span>@endif
                                    </summary>
                                    <div class="mt-2 min-w-[260px] rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                        <textarea wire:model="businessNotes.{{ $record->id }}" rows="3" maxlength="2000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nhập ghi chú quản trị..."></textarea>
                                        <p class="mt-1.5 text-[11px] leading-4 text-slate-500">Ghi chú được lưu cùng phân loại khi bấm Lưu.</p>
                                    </div>
                                </details>
                            </td>
                            <td class="px-4 py-4"><button type="button" wire:click="saveAnnotation({{ $record->id }})" wire:loading.attr="disabled" wire:target="saveAnnotation({{ $record->id }})" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="saveAnnotation({{ $record->id }})">Lưu</span><span wire:loading wire:target="saveAnnotation({{ $record->id }})">Đang lưu...</span></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-14 text-center text-sm text-gray-500">Không có dữ liệu nguồn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-200 lg:hidden">
            @forelse ($records as $record)
                @php($invoice = $record->invoice)
                <article class="space-y-4 p-4 sm:p-5">
                    <div><div class="font-bold text-slate-950">#{{ $invoice?->invoice_number ?: '—' }} <span class="font-medium text-slate-500">{{ $invoice?->symbol }}</span></div><div class="mt-1 font-semibold text-slate-800">{{ $invoice?->name ?: 'Không rõ nhà cung cấp' }}</div><div class="mt-1 text-xs text-slate-500">{{ $invoice?->issued_date?->format('d/m/Y') ?: '—' }} · MST {{ $invoice?->tax_code ?: '—' }} · {{ $invoice?->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</div></div>
                    <div class="flex flex-wrap gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->header_payload ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $record->header_payload ? 'Header đã lưu' : 'Thiếu header' }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->detail_status === 'READY' ? 'bg-emerald-50 text-emerald-700' : ($record->detail_status === 'ERROR' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $record->detail_status === 'READY' ? 'Chi tiết sẵn sàng' : ($detailLabels[$record->detail_status] ?? 'Chưa rõ') }}</span></div>
                    @if ($record->last_error)<div class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">{{ $record->last_error }}</div>@endif
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phân loại</span><select wire:model="businessClassifications.{{ $record->id }}" class="{{ $controlClass }}">@foreach ($classificationOptions as $option)<option value="{{ $option }}">{{ $classificationLabels[$option] }}</option>@endforeach</select></label>
                    <label class="flex items-start gap-2 text-xs font-medium text-slate-700"><input type="checkbox" wire:model="applySameTaxCode.{{ $record->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Áp dụng cho toàn bộ nhà cung cấp<span class="mt-0.5 block font-normal text-slate-500">MST {{ $invoice?->tax_code ?: '—' }} · các hóa đơn cùng loại</span></span></label>
                    <details class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5"><summary class="cursor-pointer select-none text-sm font-semibold text-slate-700">{{ trim((string) ($businessNotes[$record->id] ?? '')) !== '' ? 'Sửa ghi chú quản trị' : 'Thêm ghi chú quản trị' }}</summary><div class="mt-3"><textarea wire:model="businessNotes.{{ $record->id }}" rows="3" maxlength="2000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nhập ghi chú quản trị..."></textarea><p class="mt-1.5 text-xs text-slate-500">Ghi chú được lưu cùng phân loại khi bấm Lưu.</p></div></details>
                    <button type="button" wire:click="saveAnnotation({{ $record->id }})" wire:loading.attr="disabled" wire:target="saveAnnotation({{ $record->id }})" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="saveAnnotation({{ $record->id }})">Lưu phân loại</span><span wire:loading wire:target="saveAnnotation({{ $record->id }})">Đang lưu...</span></button>
                </article>
            @empty
                <div class="px-4 py-14 text-center text-sm text-gray-500">Không có dữ liệu nguồn phù hợp bộ lọc.</div>
            @endforelse
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">Hiển thị {{ number_format($records->count()) }} / {{ number_format($records->total()) }} kết quả</p>
            @if ($records->hasPages())<div>{{ $records->links('Invoices::vendor.pagination.admin-source-data') }}</div>@endif
        </div>
    </section>
</div>
