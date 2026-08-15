<div class="space-y-6">
    @if ($pdfNotice)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ $pdfNotice }}
        </div>
    @endif

    @if ($pdfError)
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
            {{ $pdfError }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Bán ra', $totalSoldAmount, $totalSoldCustomers], ['Mua vào', $totalPurchaseAmount, $totalPurchaseCustomers]] as [$label, $amount, $customers])
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:col-span-1 lg:col-span-2">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($amount) }} ₫</p>
                <p class="mt-1 text-sm text-gray-500">{{ number_format($customers) }} đối tác</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Bộ lọc hóa đơn</h3>
                <p class="mt-1 text-sm text-gray-500">Lọc theo kỳ, đối tác, MST và sắp xếp dữ liệu kế toán.</p>
            </div>

            @if ($year !== '')
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
                    Kỳ: {{ $month !== '' ? 'Tháng '.str_pad($month, 2, '0', STR_PAD_LEFT).' / ' : '' }}{{ $year }}
                </span>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <select wire:model.live="type" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="">Tất cả loại</option>
                <option value="sold">Bán ra</option>
                <option value="purchase">Mua vào</option>
            </select>

            <x-select-search id="invoice-partner-search" wire:model="name" options-wire="nameList" placeholder="Tìm đối tác...">
                <option value="">Tất cả đối tác</option>
                @foreach ($nameList as $item)
                    <option value="{{ $item }}" @selected($name === $item)>{{ $item }}</option>
                @endforeach
            </x-select-search>

            <x-select-search id="invoice-tax-code-search" wire:model="tax_code" options-wire="taxCodeList" placeholder="Tìm mã số thuế...">
                <option value="">Tất cả MST</option>
                @foreach ($taxCodeList as $item)
                    <option value="{{ $item }}" @selected($tax_code === $item)>{{ $item }}</option>
                @endforeach
            </x-select-search>

            <select wire:model.live="year" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="">Tất cả năm</option>
                @foreach ($yearOptions as $yearOption)
                    <option value="{{ $yearOption }}">Năm {{ $yearOption }}</option>
                @endforeach
            </select>

            <select wire:model.live="month" @disabled($year === '') class="rounded-xl border border-gray-300 px-4 py-3 text-sm disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400">
                <option value="">Cả năm</option>
                @for ($monthOption = 1; $monthOption <= 12; $monthOption++)
                    <option value="{{ $monthOption }}">Tháng {{ str_pad((string) $monthOption, 2, '0', STR_PAD_LEFT) }}</option>
                @endfor
            </select>

            <select wire:model.live="taxRateFilter" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="all">Mọi thuế suất</option>
                <option value="5">5%</option>
                <option value="8">8%</option>
                <option value="10">10%</option>
                <option value="other">Khác</option>
            </select>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Từ ngày</span>
                <input type="date" wire:model.live="from_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            </label>

            <label class="space-y-1.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Đến ngày</span>
                <input type="date" wire:model.live="to_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            </label>

            <label class="space-y-1.5 md:col-span-2 xl:col-span-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sắp xếp</span>
                <select wire:model.live="sort" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                    <option value="date_desc">Ngày mới nhất</option>
                    <option value="date_asc">Ngày cũ nhất</option>
                    <option value="amount_desc">Số tiền: cao → thấp</option>
                    <option value="amount_asc">Số tiền: thấp → cao</option>
                    <option value="invoice_desc">Số hóa đơn: giảm dần</option>
                    <option value="invoice_asc">Số hóa đơn: tăng dần</option>
                    <option value="partner_asc">Đối tác: A → Z</option>
                    <option value="partner_desc">Đối tác: Z → A</option>
                </select>
            </label>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Trong bộ lọc</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($filterStats['count']) }} hóa đơn</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tiền VAT</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format((float) $filterStats['vat_amount']) }} ₫</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tổng thanh toán</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format((float) $filterStats['total_amount']) }} ₫</p>
            </div>
        </div>

        @php
            $pdfSize = (int) ($fileSummary['size'] ?? 0);
            $pdfSizeLabel = $pdfSize >= 1048576
                ? number_format($pdfSize / 1048576, 2).' MB'
                : number_format($pdfSize / 1024, 1).' KB';
        @endphp

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Kho PDF theo bộ lọc</h3>
                    <p class="mt-1 text-xs text-slate-500">Metadata file, dung lượng và đóng gói lưu trữ theo kỳ đang chọn.</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">{{ $pdfSizeLabel }}</span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">Tổng hóa đơn</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($fileSummary['total']) }}</p>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">Đã có PDF</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($fileSummary['available']) }}</p>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">Chưa có</p>
                    <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format($fileSummary['missing']) }}</p>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs text-slate-500">Lỗi tải</p>
                    <p class="mt-1 text-xl font-bold text-red-700">{{ number_format($fileSummary['error']) }}</p>
                </div>
            </div>

            @if (auth('admin')->user()?->can('invoices-download'))
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="reconcilePdfMetadata" wire:loading.attr="disabled" wire:target="reconcilePdfMetadata" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                        <span wire:loading.remove wire:target="reconcilePdfMetadata">Quét metadata PDF</span>
                        <span wire:loading wire:target="reconcilePdfMetadata">Đang quét…</span>
                    </button>

                    <button type="button" wire:click="downloadMissingPdfs" wire:loading.attr="disabled" wire:target="downloadMissingPdfs" @disabled(($fileSummary['missing'] + $fileSummary['error']) === 0) class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="downloadMissingPdfs">Tải 25 PDF còn thiếu</span>
                        <span wire:loading wire:target="downloadMissingPdfs">Đang tải batch…</span>
                    </button>

                    <button type="button" wire:click="downloadPdfZip" wire:loading.attr="disabled" wire:target="downloadPdfZip" @disabled($fileSummary['available'] === 0) class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="downloadPdfZip">Tải ZIP PDF</span>
                        <span wire:loading wire:target="downloadPdfZip">Đang đóng gói…</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5">
            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="perPage" class="h-11 rounded-xl border border-gray-300 px-4 text-sm" aria-label="Số hóa đơn mỗi trang">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }} / trang</option>
                    @endforeach
                </select>

                @if (count($selected) > 0)
                    <span class="inline-flex h-9 items-center rounded-full bg-indigo-50 px-3 text-sm font-semibold text-indigo-700">
                        Đã chọn {{ number_format(count($selected)) }} hóa đơn
                    </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="resetFilters" class="h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold hover:bg-gray-50">
                    Đặt lại bộ lọc
                </button>

                @if (auth('admin')->user()?->can('invoices-export'))
                    <button type="button" wire:click="exportSelected" wire:loading.attr="disabled" wire:target="exportSelected" class="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportSelected">{{ count($selected) > 0 ? 'Xuất '.count($selected).' hóa đơn' : 'Xuất theo bộ lọc' }}</span>
                        <span wire:loading wire:target="exportSelected">Đang xuất…</span>
                    </button>
                @endif

                @if (auth('admin')->user()?->can('invoices-download'))
                    <button type="button" wire:click="downloadSelected" wire:loading.attr="disabled" wire:target="downloadSelected" @disabled(count($selected) === 0) class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="downloadSelected">{{ count($selected) > 0 ? 'Tải PDF ('.count($selected).')' : 'Tải PDF' }}</span>
                        <span wire:loading wire:target="downloadSelected">Đang tải…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3"></th>
                        <th class="px-4 py-3">Số HĐ</th>
                        <th class="px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">Đối tác</th>
                        <th class="px-4 py-3">MST</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">PDF</th>
                        <th class="px-4 py-3 text-right">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $invoice)
                        @php($pdfStatus = $pdfStatuses[$invoice->id] ?? 'missing')
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $invoice->id }}" class="rounded border-gray-300"></td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->invoice_number ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->issued_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->tax_code ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $invoice->invoice_type === 'sold' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $invoice->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($pdfStatus === 'available')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã có PDF</span>
                                        <a href="{{ route('admin.invoices.download-invoice', ['invoice' => $invoice->id]) }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Mở</a>
                                        @if (auth('admin')->user()?->can('invoices-download'))
                                            <button type="button" wire:click="downloadPdf({{ $invoice->id }}, true)" wire:loading.attr="disabled" wire:target="downloadPdf({{ $invoice->id }}, true)" class="text-xs font-semibold text-gray-600 hover:text-gray-900 disabled:opacity-50">
                                                <span wire:loading.remove wire:target="downloadPdf({{ $invoice->id }}, true)">Tải lại</span>
                                                <span wire:loading wire:target="downloadPdf({{ $invoice->id }}, true)">Đang tải…</span>
                                            </button>
                                        @endif
                                    </div>
                                @elseif ($pdfStatus === 'unsupported')
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500">Thiếu định danh GDT</span>
                                @else
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Chưa có PDF</span>
                                        @if (auth('admin')->user()?->can('invoices-download'))
                                            <button type="button" wire:click="downloadPdf({{ $invoice->id }})" wire:loading.attr="disabled" wire:target="downloadPdf({{ $invoice->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                                                <span wire:loading.remove wire:target="downloadPdf({{ $invoice->id }})">Tải PDF</span>
                                                <span wire:loading wire:target="downloadPdf({{ $invoice->id }})">Đang tải…</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($invoice->total_amount) }} ₫</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">Không có hóa đơn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="border-t border-gray-200 px-4 py-4">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
