<div class="space-y-6">
    <div wire:loading.flex wire:target="exportExcel" class="fixed inset-0 z-[130] items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-11 w-11 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang chuẩn bị file Excel</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Hệ thống đang tổng hợp {{ count($selected) > 0 ? number_format(count($selected)).' đối tác đã chọn' : 'toàn bộ đối tác theo bộ lọc' }}. File sẽ tự tải xuống khi hoàn tất.</p>
        </div>
    </div>

    @if($partnerDetail)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/50 px-4 py-8 backdrop-blur-sm" wire:click.self="closePartnerDetail">
            <div class="w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Chi tiết đối tác</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">{{ $partnerDetail['partner_name'] }}</h3>
                        <p class="mt-1 text-sm text-gray-500">MST: {{ $partnerDetail['partner_tax_code'] }} · Kỳ {{ $from_date ?: 'Tất cả' }} → {{ $to_date ?: 'Tất cả' }}</p>
                    </div>
                    <button type="button" wire:click="closePartnerDetail" class="flex h-10 w-10 items-center justify-center rounded-full text-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">×</button>
                </div>
                <div class="p-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p class="text-xs font-semibold uppercase text-sky-700">Bán ra · đã VAT</p><p class="mt-2 text-xl font-bold text-sky-900">{{ number_format($partnerDetail['sold_total']) }} ₫</p><p class="mt-1 text-xs text-sky-700">{{ number_format($partnerDetail['sold_count']) }} hóa đơn bán ra</p></div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4"><p class="text-xs font-semibold uppercase text-amber-700">Mua vào · đã VAT</p><p class="mt-2 text-xl font-bold text-amber-900">{{ number_format($partnerDetail['purchase_total']) }} ₫</p><p class="mt-1 text-xs text-amber-700">{{ number_format($partnerDetail['purchase_count']) }} hóa đơn mua vào</p></div>
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4"><p class="text-xs font-semibold uppercase text-indigo-700">VAT đầu ra</p><p class="mt-2 text-xl font-bold text-indigo-900">{{ number_format($partnerDetail['sold_vat']) }} ₫</p></div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4"><p class="text-xs font-semibold uppercase text-emerald-700">VAT đầu vào</p><p class="mt-2 text-xl font-bold text-emerald-900">{{ number_format($partnerDetail['purchase_vat']) }} ₫</p></div>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Chênh lệch tổng tiền</p><p class="mt-2 text-2xl font-bold {{ $partnerDetail['total_difference'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $partnerDetail['total_difference'] >= 0 ? '+' : '' }}{{ number_format($partnerDetail['total_difference']) }} ₫</p><p class="mt-2 text-xs text-gray-500">Bán ra đã VAT − Mua vào đã VAT. Chỉ là số đối chiếu theo đối tác, không phải lợi nhuận.</p></div>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Chênh lệch VAT</p><p class="mt-2 text-2xl font-bold {{ $partnerDetail['vat_difference'] >= 0 ? 'text-indigo-700' : 'text-amber-700' }}">{{ $partnerDetail['vat_difference'] >= 0 ? '+' : '' }}{{ number_format($partnerDetail['vat_difference']) }} ₫</p><p class="mt-2 text-xs text-gray-500">VAT đầu ra − VAT đầu vào của riêng đối tác trong kỳ đang lọc.</p></div>
                    </div>
                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        @if($partnerDetail['partner_tax_code'] !== '-')
                            <a href="{{ route('admin.invoices.hoadon-list', ['tax_code'=>$partnerDetail['partner_tax_code'],'from_date'=>$from_date,'to_date'=>$to_date]) }}" class="inline-flex h-11 items-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">Xem hóa đơn của đối tác</a>
                        @endif
                        <button type="button" wire:click="closePartnerDetail" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php($controlClass='h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400')

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Bộ lọc báo cáo</h2>
                    <p class="mt-1 text-sm text-gray-500">Chọn kỳ báo cáo trước, sau đó thu hẹp theo loại hóa đơn, đối tác và mã số thuế.</p>
                </div>
                <a href="{{ route('admin.invoices.hoadon-list') }}" class="inline-flex h-11 items-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Danh sách hóa đơn</a>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Kỳ báo cáo</h3>
                    <p class="mt-1 text-xs text-slate-500">Có thể chọn nhanh theo năm/tháng hoặc nhập khoảng ngày cụ thể.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Năm</span><select wire:model.live="year" class="{{ $controlClass }}"><option value="">Tất cả năm</option>@foreach($yearOptions as $option)<option value="{{ $option }}">Năm {{ $option }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tháng</span><select wire:model.live="month" @disabled($year==='') class="{{ $controlClass }}"><option value="">Cả năm</option>@for($m=1;$m<=12;$m++)<option value="{{ $m }}">Tháng {{ str_pad((string)$m,2,'0',STR_PAD_LEFT) }}</option>@endfor</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Từ ngày</span><input wire:model.live="from_date" type="date" class="{{ $controlClass }}"></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Đến ngày</span><input wire:model.live="to_date" type="date" class="{{ $controlClass }}"></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sắp xếp</span><select wire:model.live="sort" class="{{ $controlClass }}"><option value="sold_desc">Bán ra cao nhất</option><option value="purchase_desc">Mua vào cao nhất</option><option value="invoice_desc">Nhiều hóa đơn nhất</option><option value="partner_asc">Đối tác A → Z</option><option value="partner_desc">Đối tác Z → A</option></select></label>
                </div>
            </section>

            <section>
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Điều kiện lọc</h3>
                    <p class="mt-1 text-xs text-slate-500">Dùng khi cần tra cứu hoặc xuất báo cáo cho một nhóm đối tác cụ thể.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Loại hóa đơn</span><select wire:model.live="type" class="{{ $controlClass }}"><option value="">Tất cả loại</option><option value="sold">Bán ra</option><option value="purchase">Mua vào</option></select></label>
                    <div class="xl:col-span-2"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Đối tác</span><x-select-search id="partner-report-name-search" wire:model="name" options-wire="nameList" placeholder="Tìm đối tác..."><option value="">Tất cả đối tác</option>@foreach($nameList as $item)<option value="{{ $item }}" @selected($name===$item)>{{ $item }}</option>@endforeach</x-select-search></div>
                    <div><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Mã số thuế</span><x-select-search id="partner-report-tax-code-search" wire:model="tax_code" options-wire="taxCodeList" placeholder="Tìm MST..."><option value="">Tất cả MST</option>@foreach($taxCodeList as $item)<option value="{{ $item }}" @selected($tax_code===$item)>{{ $item }}</option>@endforeach</x-select-search></div>
                </div>
            </section>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-h-6">
                    @if(count($selected) > 0)
                        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2.5 text-sm text-indigo-800">
                            <span class="font-semibold">Đã chọn {{ number_format(count($selected)) }} đối tác.</span>
                            <button type="button" wire:click="clearSelection" class="font-semibold underline">Bỏ chọn</button>
                        </div>
                    @else
                        <p class="text-xs text-gray-500">Không chọn checkbox: Excel sẽ xuất toàn bộ dữ liệu theo bộ lọc hiện tại.</p>
                    @endif
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="resetFilters" class="h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đặt lại</button>
                    @if(auth('admin')->user()?->can('invoices-export'))
                        <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="h-11 min-w-[180px] rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                            <span wire:loading.remove wire:target="exportExcel">{{ count($selected) > 0 ? 'Xuất '.count($selected).' đối tác' : 'Xuất tất cả theo bộ lọc' }}</span>
                            <span wire:loading wire:target="exportExcel">Đang tạo Excel...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><div class="text-xs font-semibold uppercase text-gray-500">Số hóa đơn</div><div class="mt-2 text-2xl font-bold">{{ number_format($summary['invoice_count']) }}</div></div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-5"><div class="text-xs font-semibold uppercase text-sky-700">Tổng bán ra · đã VAT</div><div class="mt-2 whitespace-nowrap text-xl font-bold text-sky-900">{{ number_format($summary['sold_total']) }} ₫</div></div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5"><div class="text-xs font-semibold uppercase text-amber-700">Tổng mua vào · đã VAT</div><div class="mt-2 whitespace-nowrap text-xl font-bold text-amber-900">{{ number_format($summary['purchase_total']) }} ₫</div></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if($selectPage && !$selectAllFiltered && $partners->total() > count($selected))
            <div class="border-b border-indigo-100 bg-indigo-50 px-4 py-3 text-center text-sm text-indigo-800">
                Đã chọn {{ number_format(count($selected)) }} đối tác trên trang hiện tại.
                <button type="button" wire:click="selectAllFilteredResults" class="font-bold underline">Chọn toàn bộ {{ number_format($partners->total()) }} đối tác theo bộ lọc</button>
            </div>
        @elseif($selectAllFiltered)
            <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-800">
                Đã chọn toàn bộ {{ number_format(count($selected)) }} đối tác theo bộ lọc.
                <button type="button" wire:click="clearSelection" class="underline">Bỏ chọn tất cả</button>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="w-12 px-4 py-3"><input type="checkbox" wire:model.live="selectPage" wire:change="togglePageSelection" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" title="Chọn tất cả đối tác trên trang"></th>
                        <th class="min-w-[360px] px-4 py-3">Đối tác</th>
                        <th class="min-w-[150px] px-4 py-3 whitespace-nowrap">MST</th>
                        <th class="min-w-[90px] px-4 py-3 text-right whitespace-nowrap">Số HĐ</th>
                        <th class="min-w-[190px] px-4 py-3 text-right whitespace-nowrap">Bán ra <span class="block normal-case text-[10px] font-medium text-gray-400">Đã có VAT</span></th>
                        <th class="min-w-[190px] px-4 py-3 text-right whitespace-nowrap">Mua vào <span class="block normal-case text-[10px] font-medium text-gray-400">Đã có VAT</span></th>
                        <th class="min-w-[180px] px-4 py-3 text-right whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($partners as $partner)
                        @php($detailKey=base64_encode(json_encode(['name'=>$partner->partner_name,'tax_code'=>$partner->partner_tax_code],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)))
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $detailKey }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $partner->partner_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $partner->partner_tax_code }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ number_format($partner->invoice_count) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-sky-700 whitespace-nowrap">{{ number_format($partner->sold_total) }} ₫</td>
                            <td class="px-4 py-3 text-right font-semibold text-amber-700 whitespace-nowrap">{{ number_format($partner->purchase_total) }} ₫</td>
                            <td class="px-4 py-3 whitespace-nowrap"><div class="flex justify-end gap-2"><button type="button" wire:click="showPartnerDetail('{{ $detailKey }}')" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">Chi tiết</button>@if($partner->partner_tax_code!=='-')<a href="{{ route('admin.invoices.hoadon-list',['tax_code'=>$partner->partner_tax_code,'from_date'=>$from_date,'to_date'=>$to_date]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Xem hóa đơn</a>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">Không có dữ liệu phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-4">
            <select wire:model.live="perPage" class="h-10 rounded-xl border-gray-300 text-sm"><option value="10">10 / trang</option><option value="25">25 / trang</option><option value="50">50 / trang</option><option value="100">100 / trang</option></select>
            @if($partners->hasPages())<div>{{ $partners->links('Invoices::components.invoice-pagination') }}</div>@endif
        </div>
    </div>
</div>
