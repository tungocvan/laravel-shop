<div class="space-y-6">
    <div wire:loading.flex wire:target="downloadSelected,reconcilePdfMetadata,downloadMissingPdfs,retryPdfErrors,downloadPdfZip,deleteSelectedPdfs" class="fixed inset-0 z-[100] items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang xử lý PDF hóa đơn</h3>
            <p class="mt-2 text-sm text-slate-500">Vui lòng chờ đến khi tác vụ hoàn tất. Không đóng tab hoặc refresh trang.</p>
        </div>
    </div>

    @if(in_array($monthlyPdfBatchStatus['status'] ?? null, ['queued', 'processing'], true))
        <div wire:poll.2s="refreshMonthlyPdfBatchStatus" class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="mt-1 h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-slate-900">Đang tải PDF tháng {{ str_pad((string)($monthlyPdfBatchStatus['month'] ?? $month), 2, '0', STR_PAD_LEFT) }}/{{ $monthlyPdfBatchStatus['year'] ?? $year }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Hệ thống đã chia thành các queue tối đa 25 hóa đơn để xử lý ổn định. Bạn có thể giữ nguyên trang này để theo dõi tiến độ.</p>
                    </div>
                </div>
                @php($monthlyTotal=max(1,(int)($monthlyPdfBatchStatus['total']??0)))
                @php($monthlyProcessed=(int)($monthlyPdfBatchStatus['processed']??0))
                @php($monthlyPercent=min(100,(int)round(($monthlyProcessed/$monthlyTotal)*100)))
                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-600"><span>{{ number_format($monthlyProcessed) }} / {{ number_format((int)($monthlyPdfBatchStatus['total']??0)) }} hóa đơn</span><span>{{ $monthlyPercent }}%</span></div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $monthlyPercent }}%"></div></div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-3 py-2"><p class="text-xs text-slate-500">Queue hoàn tất</p><p class="mt-1 font-bold text-slate-900">{{ (int)($monthlyPdfBatchStatus['completed_chunks']??0) }}/{{ (int)($monthlyPdfBatchStatus['chunks']??0) }}</p></div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2"><p class="text-xs text-emerald-700">Tải thành công</p><p class="mt-1 font-bold text-emerald-900">{{ number_format((int)($monthlyPdfBatchStatus['downloaded']??0)) }}</p></div>
                    <div class="rounded-xl bg-red-50 px-3 py-2"><p class="text-xs text-red-700">Lỗi</p><p class="mt-1 font-bold text-red-900">{{ number_format((int)($monthlyPdfBatchStatus['failed']??0)) }}</p></div>
                </div>
            </div>
        </div>
    @elseif(in_array($monthlyPdfBatchStatus['status'] ?? null, ['completed', 'completed_with_errors'], true))
        <div class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full {{ ($monthlyPdfBatchStatus['failed']??0)>0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} text-2xl font-bold">{{ ($monthlyPdfBatchStatus['failed']??0)>0 ? '!' : '✓' }}</div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Đồng bộ PDF tháng đã hoàn tất</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Tháng {{ str_pad((string)($monthlyPdfBatchStatus['month']??$month),2,'0',STR_PAD_LEFT) }}/{{ $monthlyPdfBatchStatus['year']??$year }}: tải mới {{ number_format((int)($monthlyPdfBatchStatus['downloaded']??0)) }} PDF, lỗi {{ number_format((int)($monthlyPdfBatchStatus['failed']??0)) }}.</p>
                @if(($monthlyPdfBatchStatus['failed']??0)>0)<p class="mt-2 text-xs text-amber-700">Các hóa đơn lỗi vẫn được giữ trạng thái để bạn lọc “Lỗi tải PDF” và thử lại sau.</p>@endif
                <div class="mt-5 flex justify-end"><button type="button" wire:click="dismissMonthlyPdfBatchStatus" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Đóng</button></div>
            </div>
        </div>
    @endif

    @if($downloadStatus === 'success' && $pdfNotice)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">✓</div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Tải PDF hoàn tất</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pdfNotice }}</p>
                <p class="mt-2 text-xs text-slate-500">Các hóa đơn đã có PDF được giữ nguyên và không tải lại.</p>
                <div class="mt-5 flex justify-end"><button type="button" @click="open=false" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Đóng</button></div>
            </div>
        </div>
    @elseif($downloadStatus === 'error' && $pdfError)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-2xl font-bold text-red-700">!</div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Có PDF tải không thành công</h3>
                <p class="mt-2 text-sm leading-6 text-red-700">{{ $pdfError }}</p>
                <p class="mt-2 text-xs text-slate-500">Bạn có thể lọc “Lỗi tải PDF” hoặc dùng “Thử lại lỗi” để xử lý lại.</p>
                <div class="mt-5 flex justify-end"><button type="button" @click="open=false" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white">Đóng</button></div>
            </div>
        </div>
    @endif

    @if($pdfNotice)<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ $pdfNotice }}</div>@endif
    @if($pdfError)<div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">{{ $pdfError }}</div>@endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-sky-700">{{ $annualYear === 'Tất cả' ? 'Doanh thu bán ra — Tất cả các năm' : 'Doanh thu bán ra năm '.$annualYear }}</p>
                    <p class="mt-1 text-xs text-sky-600">{{ $annualYear === 'Tất cả' ? 'Tổng hóa đơn bán ra của toàn bộ dữ liệu.' : 'Tổng hóa đơn bán ra trong năm đang chọn.' }}</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-sky-700">{{ number_format($annualStats['sold_count']) }} HĐ</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format((float) $annualStats['sold_amount']) }} ₫</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-amber-700">{{ $annualYear === 'Tất cả' ? 'Giá trị mua vào — Tất cả các năm' : 'Giá trị mua vào năm '.$annualYear }}</p>
                    <p class="mt-1 text-xs text-amber-600">{{ $annualYear === 'Tất cả' ? 'Tổng hóa đơn mua vào của toàn bộ dữ liệu.' : 'Tổng hóa đơn mua vào trong năm đang chọn.' }}</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-700">{{ number_format($annualStats['purchase_count']) }} HĐ</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format((float) $annualStats['purchase_amount']) }} ₫</p>
        </div>
    </div>

    @php($controlClass='h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400')

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Bộ lọc hóa đơn</h3>
                    <p class="mt-1 text-sm text-gray-500">Chọn kỳ dữ liệu trước, sau đó thu hẹp theo loại hóa đơn, đối tác, MST, thuế suất và trạng thái PDF.</p>
                </div>
                @if($year!=='')<span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">Kỳ: {{ $month!==''?'Tháng '.str_pad($month,2,'0',STR_PAD_LEFT).' / ':'' }}{{ $year }}</span>@endif
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                <div class="mb-4"><h4 class="text-sm font-semibold text-slate-900">Kỳ dữ liệu</h4><p class="mt-1 text-xs text-slate-500">Mặc định hiển thị tháng hiện tại. Khi đổi năm, hai tổng quan phía trên cũng chuyển theo năm đó.</p></div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Năm</span><select wire:model.live="year" class="{{ $controlClass }}"><option value="">Tất cả năm</option>@foreach($yearOptions as $yearOption)<option value="{{ $yearOption }}">Năm {{ $yearOption }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tháng</span><select wire:model.live="month" @disabled($year==='') class="{{ $controlClass }}"><option value="">Cả năm</option>@for($m=1;$m<=12;$m++)<option value="{{ $m }}">Tháng {{ str_pad((string)$m,2,'0',STR_PAD_LEFT) }}</option>@endfor</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Từ ngày</span><input type="date" wire:model.live="from_date" class="{{ $controlClass }}"></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Đến ngày</span><input type="date" wire:model.live="to_date" class="{{ $controlClass }}"></label>
                    <div class="flex items-end"><button type="button" wire:click="resetFilters" class="h-11 w-full rounded-xl border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Đặt lại bộ lọc</button></div>
                </div>
            </section>

            <section>
                <div class="mb-4"><h4 class="text-sm font-semibold text-slate-900">Điều kiện lọc</h4><p class="mt-1 text-xs text-slate-500">Dùng khi cần tra cứu hoặc đối soát sâu trong kỳ đã chọn.</p></div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Loại hóa đơn</span><select wire:model.live="type" class="{{ $controlClass }}"><option value="">Tất cả loại</option><option value="sold">Bán ra</option><option value="purchase">Mua vào</option></select></label>
                    <div><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Đối tác</span><x-select-search id="invoice-partner-search" wire:model="name" options-wire="nameList" placeholder="Tìm đối tác..."><option value="">Tất cả đối tác</option>@foreach($nameList as $item)<option value="{{ $item }}" @selected($name===$item)>{{ $item }}</option>@endforeach</x-select-search></div>
                    <div><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Mã số thuế</span><x-select-search id="invoice-tax-code-search" wire:model="tax_code" options-wire="taxCodeList" placeholder="Tìm MST..."><option value="">Tất cả MST</option>@foreach($taxCodeList as $item)<option value="{{ $item }}" @selected($tax_code===$item)>{{ $item }}</option>@endforeach</x-select-search></div>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Trạng thái PDF</span><select wire:model.live="pdfStatusFilter" class="{{ $controlClass }}"><option value="all">Mọi trạng thái PDF</option><option value="available">Đã có PDF</option><option value="missing">Chưa có PDF</option><option value="error">Lỗi tải PDF</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Thuế suất</span><select wire:model.live="taxRateFilter" class="{{ $controlClass }}"><option value="all">Mọi thuế suất</option><option value="5">5%</option><option value="8">8%</option><option value="10">10%</option><option value="other">Khác</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sắp xếp</span><select wire:model.live="sort" class="{{ $controlClass }}"><option value="date_desc">Ngày mới nhất</option><option value="date_asc">Ngày cũ nhất</option><option value="amount_desc">Số tiền: cao → thấp</option><option value="amount_asc">Số tiền: thấp → cao</option><option value="invoice_desc">Số hóa đơn: giảm dần</option><option value="invoice_asc">Số hóa đơn: tăng dần</option><option value="partner_asc">Đối tác: A → Z</option><option value="partner_desc">Đối tác: Z → A</option></select></label>
                </div>
            </section>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><p class="text-xs font-medium uppercase text-gray-500">Trong bộ lọc</p><p class="mt-1 text-lg font-bold">{{ number_format($filterStats['count']) }} hóa đơn</p></div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3"><p class="text-xs font-medium uppercase text-sky-700">Bán ra</p><p class="mt-1 text-lg font-bold text-slate-900">{{ number_format((float)$filterStats['sold_amount']) }} ₫</p></div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"><p class="text-xs font-medium uppercase text-amber-700">Mua vào</p><p class="mt-1 text-lg font-bold text-slate-900">{{ number_format((float)$filterStats['purchase_amount']) }} ₫</p></div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><p class="text-xs font-medium uppercase text-gray-500">VAT trong bộ lọc</p><p class="mt-1 text-lg font-bold">{{ number_format((float)$filterStats['vat_amount']) }} ₫</p></div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                <div><h3 class="text-sm font-bold">Kho PDF theo bộ lọc</h3><p class="mt-1 text-xs text-slate-500">Bộ lọc chỉ thu hẹp danh sách; xóa PDF chỉ áp dụng cho hóa đơn checkbox.</p></div>
                <div class="mt-4 grid gap-3 sm:grid-cols-4">@foreach([['Tổng',$fileSummary['total']],['Đã có PDF',$fileSummary['available']],['Chưa có',$fileSummary['missing']],['Lỗi tải',$fileSummary['error']]] as [$label,$value])<div class="rounded-xl border border-slate-200 bg-white px-4 py-3"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-xl font-bold">{{ number_format($value) }}</p></div>@endforeach</div>
                @if(auth('admin')->user()?->can('invoices-download'))
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button wire:click="reconcilePdfMetadata" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Quét metadata</button>
                        @if($year !== '' && $month !== '' && $fileSummary['missing'] > 0)
                            <button wire:click="queueMonthlyPdfDownloads" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Tải tất cả PDF tháng {{ str_pad($month,2,'0',STR_PAD_LEFT) }}/{{ $year }} ({{ number_format($fileSummary['missing']) }})</button>
                        @else
                            <button wire:click="downloadMissingPdfs" @disabled(($fileSummary['missing']+$fileSummary['error'])===0) class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">Tải 25 PDF còn thiếu</button>
                        @endif
                        @if($fileSummary['error']>0)<button wire:click="retryPdfErrors" class="rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-800">Thử lại {{ min(25,$fileSummary['error']) }} lỗi</button>@endif
                        <button wire:click="downloadPdfZip" @disabled($fileSummary['available']===0) class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">Tải ZIP PDF</button>
                        @if(count($selected)>0)<button wire:click="deleteSelectedPdfs" wire:confirm="Xóa PDF của {{ count($selected) }} hóa đơn đã chọn? Dữ liệu hóa đơn không bị xóa." class="rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700">Xóa PDF đã chọn ({{ count($selected) }})</button>@endif
                    </div>
                    @if($year === '' || $month === '')<p class="mt-3 text-xs text-slate-500">Chọn cụ thể <strong>Năm + Tháng</strong> để xuất hiện nút tải toàn bộ PDF của tháng. Mỗi queue xử lý tối đa 25 hóa đơn.</p>@endif
                @endif
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-3"><label><span class="sr-only">Số hóa đơn mỗi trang</span><select wire:model.live="perPage" class="{{ $controlClass }} !w-auto min-w-32">@foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></label>@if(count($selected)>0)<span class="rounded-full bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">Đã chọn {{ count($selected) }} hóa đơn</span><button wire:click="clearSelection" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Bỏ chọn</button>@endif</div>
                <div class="flex flex-wrap gap-2">@if(auth('admin')->user()?->can('invoices-export'))<button wire:click="exportSelected" class="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white">{{ count($selected)>0?'Xuất '.count($selected).' hóa đơn':'Xuất theo bộ lọc' }}</button>@endif @if(auth('admin')->user()?->can('invoices-download'))<button wire:click="downloadSelected" wire:loading.attr="disabled" wire:target="downloadSelected" @disabled(count($selected)===0) class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white disabled:opacity-40">Tải PDF ({{ count($selected) }})</button>@endif</div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if($selectPage && !$selectAllFiltered && $invoices->total()>count($selected))<div class="border-b border-indigo-100 bg-indigo-50 px-4 py-3 text-center text-sm text-indigo-800">Đã chọn {{ count($selected) }} hóa đơn trên trang này. <button wire:click="selectAllFilteredResults" class="font-bold underline">Chọn toàn bộ {{ number_format($invoices->total()) }} hóa đơn theo bộ lọc</button></div>@elseif($selectAllFiltered)<div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-800">Đã chọn toàn bộ {{ number_format(count($selected)) }} hóa đơn theo bộ lọc. <button wire:click="clearSelection" class="underline">Bỏ chọn tất cả</button></div>@endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3"><input type="checkbox" wire:model.live="selectPage" wire:change="togglePageSelection" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" title="Chọn tất cả hóa đơn trang hiện tại"></th><th class="px-4 py-3 whitespace-nowrap">Số HĐ</th><th class="px-4 py-3 whitespace-nowrap">Ngày</th><th class="min-w-[320px] px-4 py-3">Đối tác</th><th class="min-w-[140px] px-4 py-3 whitespace-nowrap">MST</th><th class="min-w-[110px] px-4 py-3 whitespace-nowrap">Loại</th><th class="min-w-[170px] px-4 py-3 whitespace-nowrap">PDF</th><th class="min-w-[150px] px-4 py-3 text-right whitespace-nowrap">Tổng tiền</th></tr></thead>
                <tbody class="divide-y divide-gray-100">@forelse($invoices as $invoice)@php($pdfStatus=$pdfStatuses[$invoice->id]??'missing')<tr class="hover:bg-gray-50"><td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $invoice->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td><td class="px-4 py-3 font-medium whitespace-nowrap">{{ $invoice->invoice_number?:'-' }}</td><td class="px-4 py-3 whitespace-nowrap">{{ $invoice->issued_date?->format('d/m/Y')??'-' }}</td><td class="px-4 py-3">{{ $invoice->name?:'-' }}</td><td class="px-4 py-3 whitespace-nowrap">{{ $invoice->tax_code?:'-' }}</td><td class="px-4 py-3 whitespace-nowrap"><span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $invoice->invoice_type==='sold'?'bg-sky-50 text-sky-700':'bg-amber-50 text-amber-700' }}">{{ $invoice->invoice_type==='sold'?'Bán ra':'Mua vào' }}</span></td><td class="px-4 py-3 whitespace-nowrap">@if($pdfStatus==='available')<span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Đã có PDF</span> <a href="{{ route('admin.invoices.download-invoice',['invoice'=>$invoice->id]) }}" target="_blank" class="ml-2 whitespace-nowrap text-xs font-semibold text-indigo-600">Mở</a>@elseif($pdfStatus==='error')<span class="whitespace-nowrap text-xs font-semibold text-red-700">Lỗi tải</span>@elseif($pdfStatus==='unsupported')<span class="whitespace-nowrap text-xs text-gray-500">Thiếu định danh</span>@else<span class="inline-flex whitespace-nowrap rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Chưa có PDF</span>@endif</td><td class="px-4 py-3 text-right font-semibold whitespace-nowrap">{{ number_format($invoice->total_amount) }} ₫</td></tr>@empty<tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">Không có hóa đơn phù hợp bộ lọc.</td></tr>@endforelse</tbody>
            </table>
        </div>
        @if($invoices->hasPages())<div class="border-t border-gray-200 px-4 py-4">{{ $invoices->links('Invoices::components.invoice-pagination') }}</div>@endif
    </div>
</div>
