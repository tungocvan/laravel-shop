<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">Bộ lọc báo cáo</h2>
                <p class="mt-1 text-sm text-gray-500">Tổng hợp bán ra, mua vào và VAT theo từng đối tác.</p>
            </div>
            <a href="{{ route('admin.invoices.hoadon-list') }}" class="inline-flex h-11 items-center rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Danh sách hóa đơn</a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Loại hóa đơn</span>
                <select wire:model.live="type" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm">
                    <option value="">Tất cả loại</option>
                    <option value="sold">Bán ra</option>
                    <option value="purchase">Mua vào</option>
                </select>
            </label>

            <div class="space-y-1.5 xl:col-span-2">
                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Đối tác</span>
                <x-select-search id="partner-report-name-search" wire:model="name" options-wire="nameList" placeholder="Tìm đối tác...">
                    <option value="">Tất cả đối tác</option>
                    @foreach($nameList as $item)
                        <option value="{{ $item }}" @selected($name === $item)>{{ $item }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div class="space-y-1.5">
                <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Mã số thuế</span>
                <x-select-search id="partner-report-tax-code-search" wire:model="tax_code" options-wire="taxCodeList" placeholder="Tìm MST...">
                    <option value="">Tất cả MST</option>
                    @foreach($taxCodeList as $item)
                        <option value="{{ $item }}" @selected($tax_code === $item)>{{ $item }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Năm</span>
                <select wire:model.live="year" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm">
                    <option value="">Tất cả năm</option>
                    @foreach($yearOptions as $option)
                        <option value="{{ $option }}">Năm {{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tháng</span>
                <select wire:model.live="month" @disabled($year === '') class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">Cả năm</option>
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}">Tháng {{ str_pad((string)$m,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </label>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Từ ngày</span>
                <input wire:model.live="from_date" type="date" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm">
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Đến ngày</span>
                <input wire:model.live="to_date" type="date" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm">
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sắp xếp</span>
                <select wire:model.live="sort" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm">
                    <option value="sold_desc">Bán ra cao nhất</option>
                    <option value="purchase_desc">Mua vào cao nhất</option>
                    <option value="invoice_desc">Nhiều hóa đơn nhất</option>
                    <option value="vat_desc">VAT cao nhất</option>
                    <option value="net_desc">Chênh lệch cao nhất</option>
                    <option value="partner_asc">Đối tác A → Z</option>
                    <option value="partner_desc">Đối tác Z → A</option>
                </select>
            </label>

            <div class="flex items-end justify-end gap-2">
                <button type="button" wire:click="resetFilters" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Đặt lại</button>
                @if(auth('admin')->user()?->can('invoices-export'))
                    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportExcel">Xuất Excel báo cáo</span>
                        <span wire:loading wire:target="exportExcel">Đang xuất…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><div class="text-xs font-semibold uppercase text-gray-500">Số hóa đơn</div><div class="mt-2 text-2xl font-bold">{{ number_format($summary['invoice_count']) }}</div></div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-5"><div class="text-xs font-semibold uppercase text-sky-700">Tổng bán ra</div><div class="mt-2 text-xl font-bold text-sky-900">{{ number_format($summary['sold_total']) }} ₫</div></div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5"><div class="text-xs font-semibold uppercase text-amber-700">Tổng mua vào</div><div class="mt-2 text-xl font-bold text-amber-900">{{ number_format($summary['purchase_total']) }} ₫</div></div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5"><div class="text-xs font-semibold uppercase text-indigo-700">VAT bán ra</div><div class="mt-2 text-xl font-bold text-indigo-900">{{ number_format($summary['sold_vat']) }} ₫</div></div>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5"><div class="text-xs font-semibold uppercase text-emerald-700">VAT mua vào</div><div class="mt-2 text-xl font-bold text-emerald-900">{{ number_format($summary['purchase_vat']) }} ₫</div></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Đối tác</th><th class="px-4 py-3">MST</th><th class="px-4 py-3 text-right">Số HĐ</th><th class="px-4 py-3 text-right">Bán ra</th><th class="px-4 py-3 text-right">Mua vào</th><th class="px-4 py-3 text-right">VAT</th><th class="px-4 py-3 text-right">Chênh lệch</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($partners as $partner)<tr class="hover:bg-gray-50/70"><td class="px-4 py-3 font-semibold text-gray-900">{{ $partner->partner_name }}</td><td class="px-4 py-3 text-gray-600">{{ $partner->partner_tax_code }}</td><td class="px-4 py-3 text-right">{{ number_format($partner->invoice_count) }}</td><td class="px-4 py-3 text-right font-semibold text-sky-700">{{ number_format($partner->sold_total) }} ₫</td><td class="px-4 py-3 text-right font-semibold text-amber-700">{{ number_format($partner->purchase_total) }} ₫</td><td class="px-4 py-3 text-right">{{ number_format($partner->vat_total) }} ₫</td><td class="px-4 py-3 text-right font-bold {{ $partner->net_total >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $partner->net_total >= 0 ? '+' : '' }}{{ number_format($partner->net_total) }} ₫</td><td class="px-4 py-3 text-right">@if($partner->partner_tax_code !== '-')<a href="{{ route('admin.invoices.hoadon-list', ['tax_code'=>$partner->partner_tax_code,'from_date'=>$from_date,'to_date'=>$to_date]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Xem hóa đơn</a>@endif</td></tr>@empty<tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">Không có dữ liệu phù hợp bộ lọc.</td></tr>@endforelse
        </tbody></table></div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-4"><select wire:model.live="perPage" class="h-10 rounded-xl border-gray-300 text-sm"><option value="10">10 / trang</option><option value="25">25 / trang</option><option value="50">50 / trang</option><option value="100">100 / trang</option></select>@if($partners->hasPages())<div>{{ $partners->links('Invoices::components.invoice-pagination') }}</div>@endif</div>
    </div>
</div>
