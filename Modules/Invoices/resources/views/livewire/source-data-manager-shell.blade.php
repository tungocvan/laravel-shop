<div class="space-y-6">
    @if ($invoiceType === 'sold' && $stats['unclassified'] > 0)
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4 shadow-sm sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Hóa đơn bán ra · còn {{ number_format($stats['unclassified']) }} chưa phân loại</p>
                    <h2 class="mt-1 text-base font-bold text-emerald-950">Phân loại nhanh hóa đơn bán ra chưa phân loại là Hàng hóa</h2>
                    @if ($year !== 'all' && $month !== 'all')
                        <p class="mt-1 text-sm leading-6 text-emerald-800">Phạm vi: tháng {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}. Chỉ các hóa đơn đang Chưa phân loại được chuyển thành Hàng hóa; phân loại đã lưu trước đó được giữ nguyên.</p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-amber-800">Hãy chọn một năm và một tháng cụ thể để bật thao tác phân loại hàng loạt.</p>
                    @endif
                </div>
                <button
                    type="button"
                    wire:click="applySoldMonthAsGoods"
                    wire:confirm="Áp dụng phân loại Hàng hóa cho các hóa đơn bán ra đang Chưa phân loại của tháng đang chọn? Các hóa đơn đã có phân loại sẽ được giữ nguyên."
                    wire:loading.attr="disabled"
                    wire:target="applySoldMonthAsGoods"
                    @disabled($year === 'all' || $month === 'all')
                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <span wire:loading.remove wire:target="applySoldMonthAsGoods">Áp dụng tháng này là Hàng hóa</span>
                    <span wire:loading wire:target="applySoldMonthAsGoods">Đang áp dụng...</span>
                </button>
            </div>
        </section>
    @endif

    <section
        class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5"
        x-data="{ selected: [] }"
    >
        <form id="source-data-export-form" method="POST" action="{{ route('admin.invoices.source-data.export-detail') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="invoice_type" value="{{ $invoiceType }}">
            <input type="hidden" name="partner" value="{{ $partner }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="detail_status" value="{{ $detailStatus }}">
            <input type="hidden" name="business_classification" value="{{ $businessClassification }}">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Xuất Excel dữ liệu nguồn</p>
                    <h2 class="mt-1 text-base font-bold text-slate-950">Xuất hóa đơn đã chọn hoặc toàn bộ kết quả theo filter</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Không chọn hóa đơn nào: xuất toàn bộ kết quả theo bộ lọc hiện tại. Có chọn: chỉ xuất các hóa đơn đã chọn. File giữ đúng 20 cột của mẫu đã duyệt; mỗi dòng detail là một dòng Excel.</p>

                    @if ($records->count() > 0)
                        @php($visibleSourceIds = $records->pluck('id')->map(fn ($id) => (string) $id)->values()->all())
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="flex flex-col gap-2 border-b border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <label class="inline-flex min-h-9 items-center gap-2 text-sm font-semibold text-slate-800">
                                    <input
                                        type="checkbox"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="selected.length === {{ count($visibleSourceIds) }}"
                                        @change="selected = $event.target.checked ? @js($visibleSourceIds) : []"
                                    >
                                    <span>Chọn tất cả {{ number_format($records->count()) }} hóa đơn trên trang</span>
                                </label>
                                <span class="text-xs font-semibold text-indigo-700" x-show="selected.length > 0" x-text="selected.length + ' hóa đơn đã chọn'"></span>
                            </div>
                            <div class="grid max-h-56 gap-px overflow-y-auto bg-slate-200 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($records as $record)
                                    @php($invoice = $record->invoice)
                                    <label class="flex min-h-14 cursor-pointer items-start gap-2 bg-white px-3 py-2.5 text-sm hover:bg-indigo-50/50">
                                        <input
                                            type="checkbox"
                                            name="source_ids[]"
                                            value="{{ $record->id }}"
                                            x-model="selected"
                                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span class="min-w-0">
                                            <span class="block font-semibold text-slate-900">#{{ $invoice?->invoice_number ?: '—' }} · {{ $invoice?->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $invoice?->issued_date?->format('d/m/Y') ?: '—' }} · {{ $invoice?->name ?: 'Không rõ đối tác' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <button
                    type="submit"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <span x-show="selected.length === 0">Xuất theo bộ lọc</span>
                    <span x-show="selected.length > 0" x-text="'Xuất ' + selected.length + ' hóa đơn đã chọn'"></span>
                </button>
            </div>
        </form>
    </section>

    @include('Invoices::livewire.source-data-manager')
</div>
